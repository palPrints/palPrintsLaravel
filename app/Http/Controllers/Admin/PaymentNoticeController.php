<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PrintProviderBranch;
use App\Support\OrderNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Payment notices (bank / wallet receipts) the customer sends for an order. Nothing reaches a print shop until the
 * admin approves the notice and picks the shop here: an order with payment_status "pending" (or "failed") is hidden
 * from the shops. The receipt details live in payments.gateway_payload (receipt_path, reference, sender, note).
 */
class PaymentNoticeController extends Controller
{
    private const METHODS = [
        'bank' => ['bi-bank', 'بنك فلسطين'],
        'palpay' => ['bi-phone', 'PalPay'],
        'jawwal' => ['bi-wallet2', 'جوال بي'],
        'wallet' => ['bi-phone', 'محفظة إلكترونية'],
    ];

    public function index(): View
    {
        $payments = Payment::query()
            ->with(['order.user:id,name,phone', 'order.items.product:id,name'])
            ->whereHas('order')
            ->latest()
            ->limit(100)
            ->get();

        $pending = $payments->where('status', 'pending_review')->map(fn (Payment $payment) => $this->present($payment, true))->values();
        $reviewed = $payments->whereIn('status', ['paid', 'failed'])->take(20)->map(fn (Payment $payment) => $this->present($payment, false))->values();

        return view('admin.payment-notices', ['pending' => $pending, 'reviewed' => $reviewed]);
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        $order = $this->reviewable($payment);
        if (! $order) {
            return back()->with('notice_error', 'تمت مراجعة هذا الإشعار من قبل.');
        }

        $eligible = $this->eligibleBranches($order);
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'in:'.($eligible->pluck('id')->implode(',') ?: '0')],
        ], [
            'branch_id.required' => 'اختر المطبعة التي سيُوجَّه إليها الطلب.',
            'branch_id.in' => 'هذه المطبعة لا تقدّم كل منتجات الطلب.',
        ]);

        $branch = $eligible->firstWhere('id', (int) $validated['branch_id']);

        DB::transaction(function () use ($request, $payment, $order, $branch) {
            $payload = $payment->gateway_payload ?? [];
            $payload += ['reviewed_by' => $request->user()->id, 'reviewed_at' => now()->toISOString(), 'review_result' => 'approved'];
            $payment->update(['status' => 'paid', 'paid_at' => now(), 'failure_reason' => null, 'gateway_payload' => $payload]);

            foreach ($order->items as $item) {
                $offering = $branch->branchProductOfferings->firstWhere('product_id', $item->product_id);
                $item->update([
                    'print_provider_branch_id' => $branch->id,
                    'branch_product_offering_id' => $offering->id,
                ]);
            }

            $before = $order->status;
            $order->update(['payment_status' => 'paid', 'status' => 'processing']);
            $this->record($order, $request, $before, 'تمت الموافقة على إشعار الدفع وتوجيه الطلب إلى '.$this->branchLabel($branch), ['branch_id' => $branch->id]);
        });

        OrderNotifier::paymentReviewed((int) $order->user_id, $order->order_number, true);

        // The chosen shop's owner learns about the new order.
        if ($providerUserId = $branch->printProvider()->value('user_id')) {
            OrderNotifier::newOrderForShop((int) $providerUserId, $order->order_number, $order->items->count());
        }
        $this->audit($request, 'admin.payment_notice_approved', 'الموافقة على إشعار دفع الطلب '.$order->order_number, ['order_id' => $order->id, 'payment_id' => $payment->id, 'branch_id' => $branch->id]);

        return back()->with('notice_status', 'تمت الموافقة على الطلب '.$order->order_number.' وتوجيهه إلى '.$this->branchLabel($branch).'.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $order = $this->reviewable($payment);
        if (! $order) {
            return back()->with('notice_error', 'تمت مراجعة هذا الإشعار من قبل.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'reason.required' => 'اكتب سبب الرفض ليصل للعميل.',
            'reason.min' => 'سبب الرفض قصير جدًا.',
        ]);

        DB::transaction(function () use ($request, $payment, $order, $validated) {
            $payment->update(['status' => 'failed', 'failure_reason' => $validated['reason']]);
            $before = $order->status;
            $order->update(['payment_status' => 'failed', 'status' => 'cancelled']);
            $this->record($order, $request, $before, 'رُفض إشعار الدفع: '.$validated['reason'], ['payment_id' => $payment->id]);
        });

        OrderNotifier::paymentReviewed((int) $order->user_id, $order->order_number, false, $validated['reason']);
        $this->audit($request, 'admin.payment_notice_rejected', 'رفض إشعار دفع الطلب '.$order->order_number, ['order_id' => $order->id, 'payment_id' => $payment->id]);

        return back()->with('notice_status', 'تم رفض الطلب '.$order->order_number.' وإبلاغ العميل.');
    }

    /** Streams the receipt the customer uploaded (private storage) to the admin. */
    public function receipt(Payment $payment)
    {
        $path = $payment->gateway_payload['receipt_path'] ?? null;
        $disk = Storage::disk($payment->gateway_payload['receipt_disk'] ?? 'local');
        abort_unless($path && $disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'", // keeps an uploaded SVG from running scripts
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /** The order of a notice that still waits for the admin's decision, or null. */
    private function reviewable(Payment $payment): ?Order
    {
        $order = $payment->order()->with('items')->first();

        return $payment->status === 'pending_review' && $order && $order->payment_status === 'pending_review' ? $order : null;
    }

    /**
     * Active shops that offer every product of the order. The order's current shop (the one the cart routing chose) is
     * only a suggestion until the admin approves it.
     *
     * @return \Illuminate\Support\Collection<int, PrintProviderBranch>
     */
    private function eligibleBranches(Order $order)
    {
        $productIds = $order->items->pluck('product_id')->unique()->values();
        if ($productIds->isEmpty()) {
            return collect();
        }

        return PrintProviderBranch::query()
            ->with(['printProvider:id,company_name', 'branchProductOfferings' => fn ($query) => $query->where('is_active', true)->whereIn('product_id', $productIds)])
            ->where('is_active', true)
            ->whereHas('printProvider', fn ($query) => $query->where('is_active', true)->where('approval_status', 'approved'))
            ->whereHas('branchProductOfferings', fn ($query) => $query->where('is_active', true)->whereIn('product_id', $productIds), '>=', $productIds->count())
            ->get();
    }

    private function branchLabel(PrintProviderBranch $branch): string
    {
        $city = $branch->city && $branch->city !== 'غير محدد' ? ' ('.$branch->city.')' : '';

        return ($branch->printProvider?->company_name ?? 'مطبعة').$city;
    }

    /** @return array<string, mixed> */
    private function present(Payment $payment, bool $withShops): array
    {
        $order = $payment->order;
        $payload = $payment->gateway_payload ?? [];
        [$icon, $method] = self::METHODS[$payment->method] ?? ['bi-cash-coin', $payment->method ?: 'غير محدد'];
        $suggested = $order->items->first()?->print_provider_branch_id;

        return [
            'id' => $payment->id,
            'orderNumber' => $order->order_number,
            'customer' => $order->user?->name ?? 'عميل محذوف',
            'phone' => $order->user?->phone,
            'amount' => (float) $payment->amount,
            'icon' => $icon,
            'method' => $method,
            'receiptName' => $payload['receipt_original_name'] ?? null,
            'note' => $payload['note'] ?? null,
            'receiptUrl' => ! empty($payload['receipt_path']) ? route('admin.payment-notices.receipt', $payment) : null,
            'receiptIsImage' => ! empty($payload['receipt_path']) && preg_match('/\.(jpe?g|png|webp|gif)$/i', $payload['receipt_path']) === 1,
            'items' => $order->items->map(fn ($item) => ($item->product?->name ?? 'منتج').' × '.$item->quantity)->all(),
            'date' => $payment->created_at->locale('ar')->translatedFormat('j F Y - h:i a'),
            'status' => $payment->status,
            'reason' => $payment->failure_reason,
            'approveUrl' => route('admin.payment-notices.approve', $payment),
            'rejectUrl' => route('admin.payment-notices.reject', $payment),
            'shops' => $withShops
                ? $this->eligibleBranches($order)->map(fn (PrintProviderBranch $branch) => [
                    'id' => $branch->id,
                    'label' => $this->branchLabel($branch),
                    'suggested' => $branch->id === $suggested,
                ])->values()->all()
                : [],
        ];
    }

    private function record(Order $order, Request $request, ?string $fromStatus, string $note, array $metadata): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'changed_by' => $request->user()->id,
            'from_status' => $fromStatus,
            'to_status' => $order->status,
            'note' => $note,
            'metadata' => $metadata,
        ]);
    }

    private function audit(Request $request, string $action, string $description, array $values): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => $values,
        ]);
    }
}

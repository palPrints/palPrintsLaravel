<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PrintProviderBranch;
use App\Services\Orders\ShopNotifier;
use App\Services\OrderShopChoices;
use App\Services\Stickers\StickerPdfService;
use App\Services\WhatsApp\ShopChatLink;
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

    public function index(Request $request): View
    {
        // ?order=<number> (from the orders page) shows only that order's notice.
        $onlyOrder = $request->query('order');

        $payments = Payment::query()
            ->with(['order.user', 'order.items.product', 'order.items.printFiles', 'order.items.printProviderBranch.printProvider'])
            ->whereHas('order', fn ($order) => $onlyOrder ? $order->where('order_number', $onlyOrder) : $order)
            ->latest()
            ->limit(100)
            ->get();

        $pending = $payments->where('status', 'pending_review')->map(fn (Payment $payment) => $this->present($payment, true))->values();
        $reviewed = $payments->whereIn('status', ['paid', 'failed'])->take(20)->map(fn (Payment $payment) => $this->present($payment, false))->values();

        return view('admin.payment-notices', ['pending' => $pending, 'reviewed' => $reviewed, 'onlyOrder' => $onlyOrder]);
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        $order = $this->reviewable($payment);
        if (! $order) {
            return back()->with('notice_error', 'تمت مراجعة هذا الإشعار من قبل.');
        }

        $eligible = $this->eligibleBranches($order);
        // A shop picked from the "other shops" list wins over the ticked card.
        $request->merge(['branch_id' => $request->input('branch_id_other') ?: $request->input('branch_id')]);
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
                ] + app(\App\Services\Stickers\StickerProviderPricing::class)->assignment($item, $offering));
            }

            $before = $order->status;
            $hasStickerBundle = $order->items->contains(fn ($item) => $item->item_type === \App\Models\OrderItem::TYPE_STICKER_BUNDLE);
            $order->update(['payment_status' => 'paid', 'status' => $hasStickerBundle ? 'processing-pdf' : 'processing']);
            $this->record($order, $request, $before, 'تمت الموافقة على إشعار الدفع وتوجيه الطلب إلى '.$this->branchLabel($branch), ['branch_id' => $branch->id]);
        });

        OrderNotifier::paymentReviewed((int) $order->user_id, $order->order_number, true);

        $pdfReady = $this->generateStickerPdfs($order);
        if ($pdfReady && $order->fresh()->status === 'processing-pdf') {
            $before = 'processing-pdf';
            $order->update(['status' => 'processing']);
            $this->record($order, $request, $before, 'طھظ… طھظˆظ„ظٹط¯ ظ…ظ„ظپط§طھ PDF ظ„ط­ط²ظ…ط© ط§ظ„ظ…ظ„طµظ‚ط§طھ.', ['pdf_generated' => true]);
        }

        // The chosen shop's owner learns about the new order.
        if ($pdfReady && $providerUserId = $branch->printProvider()->value('user_id')) {
            OrderNotifier::newOrderForShop((int) $providerUserId, $order->order_number, $order->items->count());
        }
        $shopNotice = $pdfReady ? app(ShopNotifier::class)->newOrder($order, (int) $branch->print_provider_id) : null;
        $this->audit($request, 'admin.payment_notice_approved', 'الموافقة على إشعار دفع الطلب '.$order->order_number, ['order_id' => $order->id, 'payment_id' => $payment->id, 'branch_id' => $branch->id]);

        // Not back(): from the page of one order (?order=) that would show an empty page; the decision goes to the full list.
        return redirect()->route('admin.payment-notices')
            ->with('notice_status', 'تمت الموافقة على الطلب '.$order->order_number.' وتوجيهه إلى '.$this->branchLabel($branch).'.')
            ->with('notice_whatsapp', $shopNotice)
            ->with('notice_error', $pdfReady ? null : 'تم اعتماد الدفع، لكن تعذر تجهيز PDF. بقي الطلب بانتظار الملف ولم يرسل للمطبعة.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $order = $this->reviewable($payment);
        if (! $order) {
            return $this->done($request, back()->with('notice_error', 'تمت مراجعة هذا الإشعار من قبل.'));
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

        return $this->done($request, redirect()->route('admin.payment-notices')->with('notice_status', 'تم رفض الطلب '.$order->order_number.' وإبلاغ العميل.'));
    }

    /** A page form redirects as usual; the reject form's fetch gets the address instead, so the flash message survives until the page opens. */
    private function done(Request $request, RedirectResponse $redirect): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        return $request->expectsJson() ? response()->json(['redirect' => $redirect->getTargetUrl()]) : $redirect;
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

    /** Active shops that offer every product of the order (see OrderShopChoices). */
    private function eligibleBranches(Order $order)
    {
        return app(OrderShopChoices::class)->eligibleBranches($order);
    }

    private function branchLabel(PrintProviderBranch $branch): string
    {
        return app(OrderShopChoices::class)->label($branch);
    }
    /** @return array<string, mixed> */
    private function present(Payment $payment, bool $withShops): array
    {
        $order = $payment->order;
        $payload = $payment->gateway_payload ?? [];
        [$icon, $method] = self::METHODS[$payment->method] ?? ['bi-cash-coin', $payment->method ?: 'غير محدد'];

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
            'shops' => $withShops ? $this->shopChoices($order) : ['top' => [], 'others' => []],
            'whatsapp' => $payment->status === 'paid' ? ShopChatLink::forOrder($order) : null,
        ];
    }

    /** The shops for the admin to choose from: the best three and the rest (see OrderShopChoices). */
    private function shopChoices(Order $order): array
    {
        return app(OrderShopChoices::class)->choices($order);
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

    private function generateStickerPdfs(Order $order): bool
    {
        $order->loadMissing('items.order', 'items.stickerBundleLines.libraryItem', 'items.stickerBundleLines.printFile');
        $pdf = app(StickerPdfService::class);

        foreach ($order->items->where('item_type', \App\Models\OrderItem::TYPE_STICKER_BUNDLE) as $item) {
            try {
                $pdf->generateForOrderItem($item);
            } catch (\Throwable $exception) {
                report($exception);
                return false;
            }
        }

        return true;
    }
}

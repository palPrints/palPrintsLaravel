<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Concerns\HandlesSupportTickets;
use App\Http\Controllers\Controller;
use App\Support\SupportTickets;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    use HandlesSupportTickets;

    private const CATEGORIES = ['طلب أو توصيل', 'الدفع والاسترداد', 'الحساب والعنوان', 'مشكلة تقنية'];

    public function edit(Request $request): View
    {
        return view('customer.support', [
            'customer' => $request->user(),
            'categories' => self::CATEGORIES,
            'tickets' => $this->ownTickets($request),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', self::CATEGORIES)],
            'reference' => ['nullable', 'string', 'max:100'],
            'subject' => ['required', 'string', 'min:5', 'max:255'],
            'message' => ['required', 'string', 'min:15', 'max:2000'],
        ]);

        $ticket = SupportTickets::create(
            $request->user(),
            $validated['category'],
            $validated['subject'],
            $validated['message'],
            $validated['reference'] ?? null,
        );

        return response()->json([
            'message' => 'تم إرسال طلب الدعم برقم '.$ticket->ticket_number.'.',
            'ticket' => SupportTickets::thread($ticket),
            'url' => route('customer.support.show', $ticket),
        ], 201);
    }
}

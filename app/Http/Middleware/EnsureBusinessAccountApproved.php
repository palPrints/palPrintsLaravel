<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessAccountApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->supportsOnboarding() && ! $user->hasApprovedBusinessAccount()) {
            $message = match ($user->approvalStatus()) {
                'draft' => 'أكمل ملفك الشخصي أولًا، ثم أرسل الحساب للتوثيق حتى تتمكن من استخدام هذه الصفحة.',
                'rejected' => 'عدّل بيانات ملفك الشخصي وأرسلها للمراجعة مرة أخرى حتى تتمكن من استخدام هذه الصفحة.',
                default => 'حسابك قيد مراجعة الإدارة. ستتمكن من استخدام هذه الصفحة بعد اعتماد الحساب.',
            };

            return redirect()
                ->route($user->dashboardRouteName())
                ->with('warning', $message);
        }

        return $next($request);
    }
}

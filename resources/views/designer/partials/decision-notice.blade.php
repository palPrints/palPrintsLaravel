{{-- A notice at the side of the page for the admin's decision on the account: the refusal (with its reason) every time the
     profile or the dashboard opens, and the approval once, the first time the dashboard opens after it. --}}
@php
    $decisionUser = auth()->user();
    $decisionStatus = $decisionUser?->approvalStatus();
    $decisionProfile = $decisionUser?->roleProfile();
    $onProfile = request()->routeIs('designer.profile');
    $onDashboard = request()->routeIs('designer.dashboard');
    $decision = null;

    if ($decisionUser && $decisionUser->supportsOnboarding() && ($onProfile || $onDashboard)) {
        if (in_array($decisionStatus, ['rejected', 'changes_requested'], true)) {
            $decision = [
                'type' => 'is-rejected',
                'icon' => 'bi-x-circle-fill',
                'title' => 'تعذّر اعتماد الحساب',
                'text' => $decisionProfile?->admin_notes ?: ($decisionProfile?->rejection_reason ?: 'راجع بياناتك وعدّلها ثم احفظها ليُعاد إرسال الطلب.'),
                'link' => $onProfile ? null : route('designer.profile'),
            ];
        } elseif ($decisionStatus === 'approved' && $onDashboard && $decisionProfile?->approved_at) {
            $seenKey = 'designer_approval_seen_'.$decisionProfile->approved_at->timestamp;
            if (! session()->has($seenKey)) {
                session()->put($seenKey, true);
                $decision = [
                    'type' => 'is-approved',
                    'icon' => 'bi-patch-check-fill',
                    'title' => 'تم قبول طلب اعتماد حسابك',
                    'text' => 'يمكنك الآن رفع التصاميم ونشرها وطلب استلام أرباحك.',
                    'link' => null,
                ];
            }
        }
    }
@endphp

@if ($decision)
    <aside class="designer-decision-notice {{ $decision['type'] }}" id="designerDecisionNotice" role="alert">
        <i class="bi {{ $decision['icon'] }}" aria-hidden="true"></i>
        <div>
            <strong>{{ $decision['title'] }}</strong>
            <p>{{ $decision['text'] }}</p>
            @if ($decision['link'])
                <a href="{{ $decision['link'] }}">تعديل البيانات</a>
            @endif
        </div>
        <button type="button" aria-label="إغلاق" onclick="document.getElementById('designerDecisionNotice').remove()"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </aside>
@endif

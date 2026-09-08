<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>طلبات اعتماد المصممين</title>
    <style>
        body { margin: 0; padding: 32px; background: #f4f6fb; color: #172052; font-family: Arial, sans-serif; }
        main { max-width: 1100px; margin: auto; }
        h1 { margin-bottom: 24px; }
        .request { margin-bottom: 16px; padding: 20px; background: #fff; border: 1px solid #e2e6f0; border-radius: 12px; }
        .request header, .actions { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .request dl { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 18px 0; }
        dt { color: #707a98; font-size: 13px; } dd { margin: 4px 0 0; font-weight: 700; }
        textarea { width: 100%; min-height: 70px; box-sizing: border-box; margin: 12px 0; padding: 10px; }
        button { padding: 9px 14px; border: 0; border-radius: 8px; color: #fff; background: #5530ed; cursor: pointer; }
        button[name="action"][value="rejected"], button[name="action"][value="changes_requested"] { background: #b52b45; }
        .empty { padding: 30px; background: #fff; border-radius: 12px; }
        @media (max-width: 700px) { body { padding: 16px; } .request dl { grid-template-columns: 1fr 1fr; } .actions { align-items: stretch; flex-direction: column; } }
    </style>
</head>
<body>
<main>
    <h1>طلبات اعتماد المصممين</h1>

    @forelse($approvalRequests as $approvalRequest)
        @php($snapshot = $approvalRequest->profile_snapshot ?? [])
        <article class="request">
            <header>
                <strong>{{ $approvalRequest->request_number }}</strong>
                <span>{{ $approvalRequest->status === 'submitted' ? 'طلب جديد' : 'قيد المراجعة' }}</span>
            </header>
            <dl>
                <div><dt>المصمم</dt><dd>{{ $approvalRequest->user->name }}</dd></div>
                <div><dt>البريد</dt><dd>{{ $approvalRequest->user->email }}</dd></div>
                <div><dt>تاريخ الإرسال</dt><dd>{{ $approvalRequest->submitted_at?->format('Y-m-d H:i') }}</dd></div>
                <div><dt>النبذة</dt><dd>{{ $snapshot['bio'] ?? '—' }}</dd></div>
                <div><dt>المهارات</dt><dd>{{ implode('، ', $snapshot['skills'] ?? []) }}</dd></div>
                <div><dt>المعرض</dt><dd>{{ $snapshot['portfolio_url'] ?? '—' }}</dd></div>
            </dl>
            <form method="POST" action="{{ route('admin.approval-requests.update', $approvalRequest) }}">
                @csrf
                @method('PATCH')
                <textarea name="admin_notes" placeholder="ملاحظات الأدمن (مطلوبة عند الرفض أو طلب التعديل)"></textarea>
                <div class="actions">
                    @if($approvalRequest->status === 'submitted')
                        <button type="submit" name="action" value="under_review">بدء المراجعة</button>
                    @endif
                    <button type="submit" name="action" value="approved">موافقة</button>
                    <button type="submit" name="action" value="changes_requested">طلب تعديلات</button>
                    <button type="submit" name="action" value="rejected">رفض</button>
                </div>
            </form>
        </article>
    @empty
        <p class="empty">لا توجد طلبات بانتظار المراجعة.</p>
    @endforelse
</main>
</body>
</html>

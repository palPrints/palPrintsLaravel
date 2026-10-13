{{-- Email verification code for a password change. Params: $field, $shell (the page's own field / input-shell classes).
     The code input only appears once the code has been sent; the form's submit button stays disabled until then. --}}
@php $codeOpen = $errors->updatePassword->has('code'); @endphp
<div class="pp-code-box" data-password-code data-url="{{ route('password.code') }}" data-open="{{ $codeOpen ? '1' : '0' }}">
    <div class="pp-code-intro">
        <div>
            <strong><i class="bi bi-envelope-check" aria-hidden="true"></i> التحقق عبر البريد الإلكتروني</strong>
            <small data-password-code-hint>لتأكيد التغيير سنرسل رمزًا من 6 أرقام إلى بريدك الإلكتروني.</small>
        </div>
        <button type="button" class="pp-code-send" data-password-code-send><i class="bi bi-send" aria-hidden="true"></i> <span>{{ $codeOpen ? 'إعادة إرسال الرمز' : 'إرسال الرمز' }}</span></button>
    </div>
    <div class="{{ $field }} pp-code-input" data-password-code-input @unless ($codeOpen) hidden @endunless>
        <span>رمز التحقق</span>
        <span class="{{ $shell }}"><i class="bi bi-123" aria-hidden="true"></i><input name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="أدخل الرمز المكوّن من 6 أرقام" @unless ($codeOpen) disabled @endunless required></span>
    </div>
</div>
@once
    <style>
        .pp-code-box { grid-column: 1 / -1; display: grid; gap: 14px; margin-top: 4px; padding: 16px; border: 1px dashed #b9cde4; border-radius: 12px; background: #f7fbff; }
        /* the password fields start at the same height, whether or not one has a hint below it */
        [class*="grid"]:has(input[name="password"]) { align-items: start; }
        .pp-code-intro { display: flex; flex-direction: column; align-items: center; gap: 12px; text-align: center; }
        .pp-code-intro strong { display: block; margin-bottom: 6px; color: #15385f; font-size: 13px; }
        .pp-code-intro strong i { margin-inline-end: 4px; color: #1677ff; }
        .pp-code-intro small { color: #637d98; font-size: 12px; }
        .pp-code-intro small.is-error { color: #dc2626; }
        .pp-code-intro small.is-ok { color: #00823a; }
        .pp-code-send { flex: 0 0 auto; height: 40px; padding: 0 18px; border: 1px solid #1677ff; border-radius: 10px; color: #1677ff; background: #fff; font: 700 13px inherit; font-family: inherit; cursor: pointer; white-space: nowrap; }
        .pp-code-send:hover:not(:disabled) { color: #fff; background: #1677ff; }
        .pp-code-send:disabled { opacity: .55; cursor: default; }
        .pp-code-input[hidden] { display: none; }
        .pp-code-input input { letter-spacing: 4px; direction: ltr; text-align: center; }
        .pp-eye { flex: 0 0 auto; display: grid; place-items: center; width: 32px; height: 32px; margin-inline-start: auto; padding: 0; border: 0; border-radius: 8px; color: #758ca5; background: transparent; cursor: pointer; }
        .pp-eye:hover { color: #1677ff; background: rgb(22 119 255 / 8%); }
        .pp-eye i { margin: 0 !important; font-size: 17px; }
        html[data-bs-theme="dark"] .pp-code-box { border-color: #334a5f; background: #141b2b; }
        html[data-bs-theme="dark"] .pp-code-intro strong { color: #bfd5ed; }
        html[data-bs-theme="dark"] .pp-code-send { background: transparent; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () { document.querySelectorAll('[data-password-code]').forEach(function (box) {
            const button = box.querySelector('[data-password-code-send]');
            const label = button.querySelector('span');
            const hint = box.querySelector('[data-password-code-hint]');
            const reveal = box.querySelector('[data-password-code-input]');
            const input = reveal.querySelector('input');
            const form = box.closest('form');
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
            let timer = null;

            function setOpen(open) {
                reveal.hidden = !open;
                input.disabled = !open;
                const submit = form.querySelector('[type="submit"]');
                if (submit) submit.disabled = !open;
                if (open) input.focus();
            }

            function say(text, kind) { hint.textContent = text; hint.className = kind || ''; }

            function countdown(seconds) {
                clearInterval(timer);
                button.disabled = true;
                let left = seconds;
                label.textContent = 'إعادة الإرسال (' + left + ')';
                timer = setInterval(function () {
                    left -= 1;
                    if (left <= 0) { clearInterval(timer); button.disabled = false; label.textContent = 'إعادة إرسال الرمز'; return; }
                    label.textContent = 'إعادة الإرسال (' + left + ')';
                }, 1000);
            }

            setOpen(box.dataset.open === '1');

            form.querySelectorAll('input[type="password"]').forEach(function (field) {
                const eye = document.createElement('button');
                eye.type = 'button';
                eye.className = 'pp-eye';
                eye.setAttribute('aria-label', 'إظهار كلمة المرور');
                eye.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>';
                eye.addEventListener('click', function () {
                    const show = field.type === 'password';
                    field.type = show ? 'text' : 'password';
                    eye.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
                    eye.firstChild.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
                });
                field.parentElement.appendChild(eye);
            });

            button.addEventListener('click', function () {
                button.disabled = true;
                say('جارٍ إرسال الرمز...');
                fetch(box.dataset.url, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } })
                    .then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        say(res.d.message || 'تعذر إرسال الرمز، حاول لاحقًا.', res.ok ? 'is-ok' : 'is-error');
                        if (res.ok) setOpen(true);
                        if (res.d.retry_after) countdown(res.d.retry_after); else button.disabled = false;
                    })
                    .catch(function () { say('تعذر إرسال الرمز، تحقق من الاتصال.', 'is-error'); button.disabled = false; });
            });
        }); });
    </script>
@endonce

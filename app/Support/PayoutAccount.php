<?php

namespace App\Support;

use Illuminate\Http\Request;

/** Where a withdrawal is paid to: the method, the account holder's name and the phone number (or IBAN, for the bank). */
class PayoutAccount
{
    /** method => [icon, label] */
    public const METHODS = [
        'bank-of-palestine' => ['bi-bank', 'بنك فلسطين'],
        'palpay' => ['bi-phone', 'PalPay'],
        'jawwal-pay' => ['bi-phone', 'جوال بي'],
    ];

    private const PHONE = '/^[0-9]{9,10}$/';

    private const IBAN = '/^PS[0-9]{2}[A-Z0-9]{25}$/';

    /** Validates the withdrawal form and returns amount, method, holder and the cleaned account. */
    public static function validate(Request $request, float $minimum): array
    {
        $method = (string) $request->input('method');
        $bank = $method === 'bank-of-palestine';

        // Spaces and dashes are typed freely; the IBAN is kept in capitals.
        $request->merge(['account' => strtoupper(preg_replace('/[\s-]+/', '', (string) $request->input('account')))]);

        return $request->validate([
            'amount' => ['required', 'numeric', 'min:'.$minimum],
            'method' => ['required', 'in:'.implode(',', array_keys(self::METHODS))],
            'holder' => ['required', 'string', 'min:3', 'max:100'],
            'account' => ['required', 'string', 'regex:'.($bank ? '/('.trim(self::PHONE, '/').'|'.trim(self::IBAN, '/').')/' : self::PHONE)],
        ], [
            'amount.min' => 'الحد الأدنى للسحب هو '.$minimum.' ₪.',
            'holder.required' => 'اكتب اسم صاحب الحساب.',
            'holder.min' => 'اسم صاحب الحساب قصير جدًا.',
            'account.required' => $bank ? 'اكتب رقم الهاتف أو الـ IBAN.' : 'اكتب رقم الهاتف.',
            'account.regex' => $bank ? 'أدخل رقم هاتف صحيح (9-10 أرقام) أو IBAN يبدأ بـ PS.' : 'أدخل رقم هاتف صحيح (9-10 أرقام).',
        ]);
    }
}

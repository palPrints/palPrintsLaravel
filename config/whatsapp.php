<?php

/*
 * WhatsApp Cloud API (Meta) — tells a print shop about a new order and lets it accept / reject from WhatsApp.
 * See docs/whatsapp-integration-plan.md. With WHATSAPP_ENABLED=false nothing is sent and the dashboard keeps working.
 */
return [

    'enabled' => (bool) env('WHATSAPP_ENABLED', false),
    // Approved utility template: DOCUMENT header and one body parameter (order number).
    'print_file_template' => env('WHATSAPP_PRINT_FILE_TEMPLATE'),
    'print_file_template_language' => env('WHATSAPP_PRINT_FILE_TEMPLATE_LANG', 'ar'),

    'graph_url' => env('WHATSAPP_GRAPH_URL', 'https://graph.facebook.com'),
    'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v23.0'),

    // WhatsApp Manager → API Setup.
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    // System-user token (never a temporary dashboard token in production). Never logged.
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

    // App Dashboard → App settings → Basic → App secret. Signs every webhook POST (X-Hub-Signature-256).
    'app_secret' => env('WHATSAPP_APP_SECRET'),
    // Any long random string; typed again in the App Dashboard webhook settings.
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

    // Country code added to a local number such as 0599123456.
    'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '970'),

    /*
     * The approved utility template sent for a new order. It must be created in WhatsApp Manager with exactly:
     *   Body (4 variables): طلب جديد رقم {{1}} لمطبعتك. المنتجات: {{2}}. الكمية: {{3}}. مستحقاتك: {{4}} شيكل. هل تقبلين تنفيذ الطلب؟
     *   Buttons: quick reply "قبول الطلب", quick reply "رفض الطلب" (in this order).
     */
    'new_order_template' => [
        'name' => env('WHATSAPP_NEW_ORDER_TEMPLATE', 'new_print_order'),
        'language' => env('WHATSAPP_NEW_ORDER_TEMPLATE_LANG', 'ar'),
    ],

    /*
     * The approved utility template that carries any other platform notification to a shop (payment, approval, support
     * reply…). Create it in WhatsApp Manager with exactly two body variables:
     *   Body: إشعار من PALPRINTS: {{1}} — {{2}}
     * Leave the name empty to send only new orders on WhatsApp.
     */
    'notification_template' => [
        'name' => env('WHATSAPP_NOTIFICATION_TEMPLATE', 'palprints_notification'),
        'language' => env('WHATSAPP_NOTIFICATION_TEMPLATE_LANG', 'ar'),
    ],

    // How long a shop has to type its rejection reason after tapping "reject".
    'reject_reason_minutes' => 30,

];

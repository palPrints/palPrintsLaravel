<?php

return [
    'failed' => 'The login details are incorrect. Check your email address and password.',
    'password' => 'The password is incorrect.',
    'throttle' => 'Too many login attempts. Try again in :seconds seconds.',

    'login' => [
        'email_required' => 'Enter your email address.',
        'email_invalid' => 'Enter a valid email address, such as name@example.com.',
        'password_required' => 'Enter your password.',
        'password_invalid' => 'Enter a valid password.',
        'inactive' => 'This account is currently suspended. Please contact PALPRINTS support.',
        'session_inactive' => 'You were signed out because your account is currently inactive. Please contact PALPRINTS support.',
    ],

    'social' => [
        'account_type_required' => 'Choose an account type before continuing with Google or Apple.',
        'terms_required' => 'You must accept the terms and privacy policy before creating an account.',
        'provider_not_configured' => 'Sign-in with :provider is currently unavailable because its configuration is incomplete.',
        'connection_failed' => 'Could not connect to :provider. Please try again.',
        'cancelled' => 'You cancelled the :provider sign-in process.',
        'settings_incomplete' => 'The :provider settings are incomplete.',
        'unable_to_complete' => 'Could not complete sign-in. Please try again.',
        'provider_login_failed' => 'Could not complete sign-in with :provider. Please try again.',
        'provider_id_missing' => 'The sign-in provider did not return the required account identifier.',
        'email_unverified' => 'We could not verify your email address with :provider.',
        'login_account_missing' => 'No PALPRINTS account is linked to this email. Create an account and choose an account type first.',
        'role_session_expired' => 'Your account-type selection expired. Choose an account type again.',
        'already_linked' => 'This account is already linked to another :provider account.',
        'inactive' => 'This account is currently suspended. Please contact PALPRINTS support.',
    ],
];

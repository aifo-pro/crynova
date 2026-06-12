<?php

return [
    'welcome_back' => 'Welcome, :name!',
    'logged_out'   => 'You have been logged out.',

    'tfa' => [
        'page_title'    => 'Set Up Two-Factor Authentication',
        'heading'       => 'Set up two-factor authentication',
        'subtitle'      => 'Scan the QR code with your authenticator app, then confirm with a code.',
        'manage_heading'  => 'Manage two-factor authentication',
        'manage_subtitle' => 'Your account is protected. You can disable 2FA here.',
        'step1'         => 'Step 1 — Scan QR code',
        'open_app'      => 'Open :a1, :a2, or any TOTP-compatible app, then scan this code.',
        'manual'        => 'Or enter the key manually:',
        'copy_key'      => 'Copy key',
        'meta'          => 'Type: Time-based (TOTP) · Digits: 6 · Period: 30s',
        'step2'         => 'Step 2 — Confirm setup',
        'code_label'    => '6-digit code from your app',
        'recovery_label'   => 'Recovery secret word',
        'recovery_hint'    => 'Remember this word. If you lose access to your authenticator, support will ask for it to disable 2FA. We cannot recover it for you.',
        'recovery_placeholder' => 'e.g. blue-whale-2024',
        'enable'        => 'Enable 2FA',
        'cancel'        => 'Cancel',
        'disable_title' => 'Disable 2FA',
        'disable_sub'   => 'You will need your current password to disable 2FA.',
        'current_pass'  => 'Current password',
        'disable'       => 'Disable 2FA',
        'code_mismatch' => 'Code does not match. Try again.',
        'invalid_code'  => 'Invalid authentication code.',
        'enabled_ok'    => '2FA enabled successfully.',
        'disabled_ok'   => '2FA disabled.',
    ],
];

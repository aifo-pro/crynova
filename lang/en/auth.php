<?php

return [
    'welcome_back' => 'Welcome, :name!',
    'logged_out'   => 'You have been logged out.',

    'tfa' => [
        'page_title'    => 'Set Up Two-Factor Authentication',
        'heading'       => 'Set up two-factor authentication',
        'subtitle'      => 'Scan the QR code with your authenticator app, then confirm with a code.',
        'step1'         => 'Step 1 — Scan QR code',
        'open_app'      => 'Open :a1, :a2, or any TOTP-compatible app, then scan this code.',
        'manual'        => 'Or enter the key manually:',
        'copy_key'      => 'Copy key',
        'meta'          => 'Type: Time-based (TOTP) · Digits: 6 · Period: 30s',
        'step2'         => 'Step 2 — Confirm setup',
        'code_label'    => '6-digit code from your app',
        'enable'        => 'Enable 2FA',
        'cancel'        => 'Cancel',
        'disable_title' => 'Disable 2FA',
        'disable_sub'   => 'You will need your current password to disable 2FA.',
        'current_pass'  => 'Current password',
        'disable'       => 'Disable 2FA',
    ],
];

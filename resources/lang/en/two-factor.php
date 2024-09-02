<?php

return [
    'title'    => 'One Time Pin is required',
    'required' => 'One Time Pin Authentication is required.',
    'back'     => 'Go back',
    'continue' => 'To continue, open up your Authenticator app and enter your OTP code.',
    'enable'   => 'You need to enable Two-Factor Authentication.',

    'success' => 'The OTP code has been validated successfully.',

    'fail_2fa' => 'The TOTP code has expired or is invalid.',
    'fail_confirm' => 'The OTP to activate Two-Factor Authentication is invalid.',
    'enabled'      => 'Two-Factor Authentication has been enabled for your account.',
    'disabled'     => 'Two-Factor Authentication has been disabled for your account.',

    'safe_device' => 'We won\'t ask you for Two-Factor Authentication codes in this device for some time.',

    'totp_or_recovery_code'   => 'One time Pin or Recovery code',
    'confirm_otp_hint'  => 'An OTP should have :otpLength digits, while a recovery code can be alphanumeric and should be :recoveryLength characters.',
    'one_time_pin'   => 'One Time Pin',
    'enable_safe_device'   => 'Enable safe device',
    'confirm'   => 'Confirm code',
    'enabled_at' => 'Enabled at',
    'switch_on' => 'Go to enable Two-Factor Authentication.',
    'disable_2fa' => 'Diable Two-Factor Authentication.',
    'generate_recovery_code' => 'Generate Recovery Code',
    'save_changes' => 'Save Changes',

    'recovery_code' => [
        'recovery_code'    => 'Reovery code',
        'toggle_recovery_login' => 'Login with recovery code',
        'error_message'   => 'The recovery code is invalid, expired, or has already been used.',
        'used'      => 'You have used a Recovery Code. Remember to regenerate them if you have used almost all.',
        'depleted'  => 'You have used all your Recovery Codes. Please use alternate authentication methods to continue.',
        'generated' => 'You have generated a new set of Recovery Codes. Any previous set of codes have been invalidated.',
    ],

    'profile_label' =>  'Two Factor Authentication',
    'profile_title' =>  'Two Factor Authentication',

    'setup_title' => 'Setup your device',
    'setup_step_1' => 'Step 1. Scan the QR Code',
    'setup_step_2' => 'Step 2. Enter the pin provided by your app',

    'action_label'  => 'Submit 2FA Pin and complete setup',

    'setup_message_1' => 'When two factor authentication is enabled, you will prompted for a secure pin during login. Your phone\'s authenticator application will provide a new pin every 60 seconds',
    'setup_message_2' => 'To enable 2FA scan the following QR code using your phone\'s authenticator application and submit TOTP code to confirm setup. ',

    'recovery_instruction' => 'Save these recovery codes in a safe place. Each code can be used once to recover account if your two factor authentication device is lost.'
];

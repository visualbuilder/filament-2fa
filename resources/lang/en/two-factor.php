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
    'enabled'      => 'Two-Factor Authentication has been enabled.',
    'disabled'     => 'Two-Factor Authentication has been disabled.',
    'enabled_message' => 'Two-Factor Authentication was enabled on :date.',

    'safe_device' => 'We won\'t ask you for Two-Factor Authentication codes in this device for some time.',

    'totp_or_recovery_code'   => 'One time Pin or Recovery code',
    'confirm_otp_hint'  => 'An OTP should have :otpLength digits, while a recovery code can be alphanumeric and should be :recoveryLength characters.',
    'one_time_pin'   => 'One Time Pin',
    'enable_safe_device'   => 'Remember this device for :days days',
    'confirm'   => 'Confirm code',
    'enabled_at' => 'Enabled at',
    'switch_on' => 'Go to enable Two-Factor Authentication.',
    'disable_2fa' => 'Disable Two-Factor Authentication.',
    'generate_recovery_code' => 'Generate new recovery codes',
    'hide_recovery_code' => 'Hide recovery codes',
    'show_recovery_code' => 'Show recovery codes',
    'save_changes' => 'Save Changes',
    'back_to_dashboard' => 'Back to Dashboard',


    'recovery_code' => [
        'recovery_code'    => 'Recovery code',
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

    'action_label'  => 'Submit TOTP Pin and complete setup',

    'setup_message_1' => 'When two factor authentication is enabled, you will prompted for a secure pin during login. Your phone\'s authenticator application will provide a new pin every :interval seconds.',
    'setup_message_2' => 'To enable 2FA, scan the following QR code using an authenticator app on your phone (such as Google Authenticator, Microsoft Authenticator, Authy, or LastPass Authenticator). Then, enter the one-time passcode (OTP) to confirm the setup.',

    'recovery_instruction' => 'Important: Store these recovery codes in a secure location. Each code can be used only once to access your account if your two-factor authentication device is lost or unavailable. If you ever lose access to your primary device, you can use these codes to regain access to your account. Make sure not to share these codes with anyone to protect your account\'s security.'

];

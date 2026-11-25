<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Laragear\TwoFactor\Models\TwoFactorAuthentication;
use Visualbuilder\Filament2fa\Filament\Pages\Login;
use Visualbuilder\Filament2fa\Filament\Pages\Confirm2Fa;

use function Pest\Livewire\livewire;

it('check confirm page without login credentials', function () {
    $this->get(url(config('filament-2fa.login.confirm_totp_page_url')))
        ->assertRedirect(Filament::getLoginUrl());
});

it('check encrypted credentials are stored on sessions', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();

    livewire(Login::class)
        ->assertFormExists()
        ->fillForm([
            'email' => $loginEmail = 'admin@domain.com',
            'password' => 'password'
        ])
        ->call('authenticate')
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    $sessionKey = config('filament-2fa.login.credential_key');
    $credentials = session("$sessionKey.credentials", []);
    expect(Crypt::decryptString($credentials['email']))->toEqual($loginEmail);
    livewire(Confirm2Fa::class)
        ->assertFormFieldExists('totp_code')
        ->assertFormExists();
});

it('Confirm 2FA TOTP code check validation errors', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();

    livewire(Login::class)
        ->assertFormExists()
        ->fillForm([
            'email' => $loginEmail = 'admin@domain.com',
            'password' => 'password'
        ])
        ->call('authenticate')
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    $sessionKey = config('filament-2fa.login.credential_key');
    $credentials = session("$sessionKey.credentials", []);
    expect(Crypt::decryptString($credentials['email']))->toEqual($loginEmail);

    // Empty code should fail validation
    livewire(Confirm2Fa::class)
        ->fillForm(['totp_code' => ''])
        ->call('submit')
        ->assertHasFormErrors(['totp_code' => 'required']);

    // Invalid code should not authenticate the user
    livewire(Confirm2Fa::class)
        ->fillForm(['totp_code' => '123456'])
        ->call('submit')
        ->assertNotified('Invalid Code');
    expect(auth()->check())->toBeFalse();
});

it('Confirm 2FA TOTP code', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();

    livewire(Login::class)
        ->assertFormExists()
        ->fillForm([
            'email' => $loginEmail = 'admin@domain.com',
            'password' => 'password'
        ])
        ->call('authenticate')
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    $sessionKey = config('filament-2fa.login.credential_key');
    $credentials = session("$sessionKey.credentials", []);
    expect(Crypt::decryptString($credentials['email']))->toEqual($loginEmail);
    livewire(Confirm2Fa::class)
        ->fillForm([
            'totp_code' => $user->makeTwoFactorCode()
        ])
        ->assertFormExists()
        ->assertHasNoFormErrors()
        ->call('submit');
    expect(auth()->user()->email)->toEqual('admin@domain.com');
});

it('does not remember the device when toggle is off', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();

    livewire(Login::class)
        ->assertFormExists()
        ->fillForm([
            'email' => 'admin@domain.com',
            'password' => 'password'
        ])
        ->call('authenticate')
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    livewire(Confirm2Fa::class)
        ->fillForm([
            'totp_code' => $user->makeTwoFactorCode(),
        ])
        ->call('submit');

    $user->refresh();
    expect($user->twoFactorAuth->safe_devices)->toBeEmpty();
});

it('remembers the device when toggle is on', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();

    livewire(Login::class)
        ->assertFormExists()
        ->fillForm([
            'email' => 'admin@domain.com',
            'password' => 'password'
        ])
        ->call('authenticate')
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    livewire(Confirm2Fa::class)
        ->fillForm([
            'totp_code' => $user->makeTwoFactorCode(),
            'safe_device_enable' => true,
        ])
        ->call('submit');

    $user->refresh();
    expect($user->twoFactorAuth->safe_devices)->not()->toBeEmpty();
});

it('signs in with a recovery code and marks the code as used', function () {
    $user = $this->createUser();
    $twoFactor = TwoFactorAuthentication::factory()->withRecovery()->make();
    $code = $twoFactor->recovery_codes->first()['code'];
    $user->twoFactorAuth()->save($twoFactor);

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin@domain.com',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    livewire(Confirm2Fa::class)
        ->fillForm(['totp_code' => $code])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified('Success');

    expect(auth()->user()?->email)->toBe('admin@domain.com');

    $used = $user->refresh()->twoFactorAuth->recovery_codes->firstWhere('code', $code);
    expect($used['used_at'])->not->toBeNull();
    expect($user->twoFactorAuth->recovery_codes->whereNull('used_at'))->toHaveCount(9);
});

it('rejects a recovery code that was already used', function () {
    $user = $this->createUser();
    $twoFactor = TwoFactorAuthentication::factory()->withRecovery()->make();
    $code = $twoFactor->recovery_codes->first()['code'];
    $twoFactor->setRecoveryCodeAsUsed($code);
    $user->twoFactorAuth()->save($twoFactor);

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin@domain.com',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    livewire(Confirm2Fa::class)
        ->fillForm(['totp_code' => $code])
        ->call('submit')
        ->assertNotified('Invalid Code');

    expect(auth()->check())->toBeFalse();
});

function startTwoFactorLogin(): void
{
    livewire(Login::class)
        ->fillForm([
            'email' => 'admin@domain.com',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));
}

function submitWrongCodes(int $times): void
{
    foreach (range(1, $times) as $attempt) {
        livewire(Confirm2Fa::class)
            ->fillForm(['totp_code' => '000000'])
            ->call('submit')
            ->assertHasNoErrors(['totp_code'])
            ->assertNotified('Invalid Code');
    }
}

function userRateLimitKey($user): string
{
    return 'filament-2fa-confirm:'.sha1($user::class.'|'.$user->getKey());
}

function ipRateLimitKey(): string
{
    return 'livewire-rate-limiter:'.sha1(Confirm2Fa::class.'|submit|'.request()->ip());
}

it('refuses the sixth code within a minute without checking it', function () {
    $this->freezeTime();
    $user = $this->createUser();
    $user->twoFactorAuth()->save(TwoFactorAuthentication::factory()->make());

    startTwoFactorLogin();
    submitWrongCodes(5);

    livewire(Confirm2Fa::class)
        ->fillForm(['totp_code' => $user->makeTwoFactorCode()])
        ->call('submit')
        ->assertHasErrors(['totp_code'])
        ->assertSee(__('auth.throttle', ['seconds' => 60]))
        ->assertNotNotified('Success');

    expect(auth()->check())->toBeFalse();
});

it('keeps the per user limit when the login is started again', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(TwoFactorAuthentication::factory()->make());

    startTwoFactorLogin();
    submitWrongCodes(5);

    // A new IP would have a fresh per IP counter, and a new login gives a fresh session
    expect(RateLimiter::attempts(ipRateLimitKey()))->toBe(5);
    RateLimiter::clear(ipRateLimitKey());
    startTwoFactorLogin();

    livewire(Confirm2Fa::class)
        ->fillForm(['totp_code' => $user->makeTwoFactorCode()])
        ->call('submit')
        ->assertHasErrors(['totp_code']);

    expect(auth()->check())->toBeFalse();
    expect(RateLimiter::tooManyAttempts(userRateLimitKey($user), 5))->toBeTrue();
});

it('allows codes again after the limit window passes', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(TwoFactorAuthentication::factory()->make());

    startTwoFactorLogin();
    submitWrongCodes(5);

    $this->travel(61)->seconds();

    livewire(Confirm2Fa::class)
        ->fillForm(['totp_code' => $user->makeTwoFactorCode()])
        ->call('submit')
        ->assertHasNoErrors(['totp_code'])
        ->assertNotified('Success');

    expect(auth()->user()?->email)->toBe('admin@domain.com');
});

it('logs in with a correct code under the limit and clears the per user counter', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(TwoFactorAuthentication::factory()->make());

    startTwoFactorLogin();
    submitWrongCodes(2);

    expect(RateLimiter::attempts(userRateLimitKey($user)))->toBe(2);

    livewire(Confirm2Fa::class)
        ->fillForm(['totp_code' => $user->makeTwoFactorCode()])
        ->call('submit')
        ->assertHasNoErrors(['totp_code'])
        ->assertNotified('Success');

    expect(auth()->user()?->email)->toBe('admin@domain.com');
    expect(RateLimiter::attempts(userRateLimitKey($user)))->toBe(0);
});

# Changelog

All notable changes to `Filament-2fa` will be documented in this file.

## 5.0.6 - 2026-11-23 Laravel 13 support

- Allow `laragear/two-factor` `^4.0` alongside `^2.0` and `^3.0`, enabling installation on Laravel 13 (laragear `^4.0` supports Laravel 12 and 13 and requires PHP 8.3)
- Allow `orchestra/testbench` `^11.0` for the Laravel 13 package tests
- Add Laravel 13 (testbench `11.*`) to the CI test matrix, and test Laravel 12 against both laragear `^3.0` and `^4.0`
- Add PHP 8.5 to the CI test matrix
- Add tests for signing in with a recovery code

## 5.0.5 - 2026-10-22  Banners on login pages

- Add a `show_to_guests` option to banners so they can show to signed-out visitors, e.g. on the login page. Banners stayed hidden there because the render middleware required a signed-in user. Opt-in, guard targeting still applies, and 2FA setup banners never show to guests
- New migration `add_show_to_guests_to_two_factor_banners_table` (publish and run when upgrading)
- Fix the banner factory's default icon name

## 5.0.4 - 2026-05-29  Laravel 12 support

- Allow `laragear/two-factor` `^3.0` alongside `^2.0`, enabling installation on Laravel 12 (laragear `^3.0` supports Laravel 11 and 12)
- Re-add Laravel 12 to the CI test matrix (testbench `10.*`)

## 1.0.0 - 202X-XX-XX

- initial release

## 4.x - 2025-08-20  Update to work with Filament 4

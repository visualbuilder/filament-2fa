# Changelog

All notable changes to `Filament-2fa` will be documented in this file.

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

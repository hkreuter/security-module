# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0] - Unreleased

### Added
- Two-factor authentication for the OXAPI: `login` / `token` queries issue a
  short-lived challenge token for 2FA-enabled users; `verifyTwoFactorLogin` /
  `verifyTwoFactorToken` mutations exchange a valid OTP for full access (and
  refresh) tokens. Requires `oxid-esales/graphql-base`.
- Module setting `oeSecurityTwoFactorAuthApiChallengeLifetime` (default 300s)
  controls the API challenge token's exchange window.
- Configurable OTP code lifetime via `oeSecurityTwoFactorAuthOtpCodeLifetime`
  (default 300 seconds). Replaces the previous hardcoded 5-minute value
  in `OtpChallengeStateService`. Applies to both the storefront/admin
  and API 2FA flows.
- Admin help texts for the four `two_factor_auth` module settings
  (`Enabled`, `Type`, `ApiChallengeLifetime`, `OtpCodeLifetime`) in
  English and German.

### Changed
- Updated to work with OXID eShop 7.5.x
- Minimum PHP version is now 8.3, tested with up to PHP 8.5
- Failed OXAPI 2FA verifications now surface as a client-aware GraphQL error
  (`Invalid or expired two-factor code`) instead of `Internal server error`.
- The API 2FA challenge JWT's `mfa_exp` claim is now clamped to
  `min(ApiChallengeLifetime, OtpCodeLifetime)`. An operator who sets
  `ApiChallengeLifetime > OtpCodeLifetime` gets the effective shorter
  lifetime rather than a Bearer that outlives the underlying OTP.

## [3.0.0] - 2026-05-12

### Added
- Two-Factor Authentication (2FA) with email OTP verification

### Fixed
- Captcha validation moved from User model to UserComponent to prevent API login failures

## [2.1.0] - 2026-01-14

### Added
- Extracted reusable Twig code into captcha.html.twig and password.html.twig

### Changed
- Show multiple errors on invalid password

## [2.0.0] - 2025-06-11
This is the stable release of v2.0.0. No changes have been made since v2.0.0-rc.3.

## [2.0.0-rc.3] - 2025-06-04

### Fixed
- Missing information from contact form email

## [2.0.0-rc.2] - 2025-05-22

### Added
- Image Captcha and Honeypot protection for checkout without registration form
- Shared/Model/User::shouldValidateCaptcha allowing customization of conditions needed to run checkValues

### Fixed
- Checkout process is no longer blocked when Image Captcha or Honeypot protection is enabled

## [2.0.0-rc.1] - 2025-04-29

### Added
- Image Captcha Protection to prevent automated bot submissions.
- Audio Captcha Support for accessibility, allowing users to hear the captcha text.
- Honeypot Captcha to detect and block bots without disrupting user experience.
- Support of PHP 8.4

### Fixed
- Password validators are no longer used when password policy is disabled
- Error in shop core settings forms

## [1.0.0] - 2024-11-27
This is the stable release of v1.0.0. No changes have been made since v1.0.0-rc.1.

## [1.0.0-rc.1] - 2024-11-06

### Added
- Password strength validators for length, uppercase, lowercase, digit and special characters
- Ajax widget for password strength visual indicator
- Frontend progress bar style indication for password strength
- Button for generating strong password
- Button for show/hide password in password fields

[3.0.0]: https://github.com/OXID-eSales/security-module/compare/v2.1.0...v3.0.0
[2.1.0]: https://github.com/OXID-eSales/security-module/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/OXID-eSales/security-module/compare/v2.0.0-rc.3...v2.0.0
[2.0.0-rc.3]: https://github.com/OXID-eSales/security-module/compare/v2.0.0-rc.2...v2.0.0-rc.3
[2.0.0-rc.2]: https://github.com/OXID-eSales/security-module/compare/v2.0.0-rc.1...v2.0.0-rc.2
[2.0.0-rc.1]: https://github.com/OXID-eSales/security-module/compare/v1.0.0...v2.0.0-rc.1
[1.0.0]: https://github.com/OXID-eSales/security-module/compare/v1.0.0-rc.1...v1.0.0
[1.0.0-rc.1]: https://github.com/OXID-eSales/security-module/releases/tag/v1.0.0-rc.1

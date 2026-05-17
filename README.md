# OXID Security Module
A collection of security features for OXID eShop

[![Development](https://github.com/OXID-eSales/security-module/actions/workflows/trigger.yaml/badge.svg?branch=b-7.5.x)](https://github.com/OXID-eSales/security-module/actions/workflows/trigger.yaml)
[![Latest Version](https://img.shields.io/packagist/v/OXID-eSales/security-module?logo=composer&label=latest&include_prereleases&color=orange)](https://packagist.org/packages/oxid-esales/security-module)
[![PHP Version](https://img.shields.io/packagist/php-v/oxid-esales/security-module)](https://github.com/oxid-esales/security-module)

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=OXID-eSales_security-module&metric=alert_status&token=0026d27eda3483728f0985d44d32714927ad2f3d)](https://sonarcloud.io/dashboard?id=OXID-eSales_security-module)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=OXID-eSales_security-module&metric=coverage&token=0026d27eda3483728f0985d44d32714927ad2f3d)](https://sonarcloud.io/dashboard?id=OXID-eSales_security-module)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=OXID-eSales_security-module&metric=sqale_index&token=0026d27eda3483728f0985d44d32714927ad2f3d)](https://sonarcloud.io/dashboard?id=OXID-eSales_security-module)

## Compatibility

This module assumes you have OXID eShop Compilation version 7.5.0 installed.

### Branches
* 4.0.0.x versions (or b-7.5.x branch) are compatible with OXID eShop compilation 7.5.x
* 3.0.0.x versions (or b-7.4.x branch) are compatible with OXID eShop compilation 7.4.x
* 2.1.0.x versions are compatible with OXID eShop compilation 7.4.x
* 2.0.0.x versions (or b-7.3.x branch) are compatible with OXID eShop compilation 7.3.x.
* 1.0.0.x versions (or b-7.2.x branch) are compatible with OXID eShop compilation 7.2.x.

# Development installation

To be able running the tests and other preconfigured quality tools, please install the module as a [root package](https://getcomposer.org/doc/04-schema.md#root-package).

The next section shows how to install the module as a root package by using the OXID eShop SDK.

In case of different environment usage, please adjust by your own needs.

# Development installation on OXID eShop SDK

The installation instructions below are shown for the current [SDK](https://github.com/OXID-eSales/docker-eshop-sdk)
for shop 7.5. Make sure your system meets the requirements of the SDK.

0. Ensure all docker containers are down to avoid port conflicts

1. Clone the SDK for the new project
```shell
echo MyProject && git clone https://github.com/OXID-eSales/docker-eshop-sdk.git $_ && cd $_
```

2. Clone the repository to the source directory
```shell
git clone --recurse-submodules https://github.com/OXID-eSales/security-module.git --branch=b-7.5.x ./source
```

3. Run the recipe to setup the development environment
```shell
./source/recipes/setup-development.sh
```

You should be able to access the shop with http://localhost.local and the admin panel with http://localhost.local/admin
(credentials: noreply@oxid-esales.com / admin)

## Features

### Password Strength Policy

This module provides password strength estimation for any string input.
It can validate password length and character variety based on configurable settings.
It also includes a visual password strength indicator with a progress bar for real-time feedback via an Ajax widget.

#### Configuration

- Enable/Disable password strength estimation
- Minimum password length
- Uppercase character requirement
- Lowercase character requirement
- Digit requirement
- Special character requirement

### Captcha Protection

The module features Image Captcha protection to prevent automated bot submissions.
Users must enter the text displayed in the captcha image, with an audio captcha option available for accessibility.
A honeypot captcha is also implemented as a hidden field to detect and block bots without affecting the user experience.

#### Configuration

- Enable/Disable Image Captcha protection
- Enable/Disable Honeypot Captcha protection
- Image Captcha lifetime (5min, 15min, 30min)

### Two-Factor Authentication (2FA)

The module provides Two-Factor Authentication using email-based One-Time Password (OTP) verification.
When enabled, users are required to enter a verification code sent to their email address after logging in with their credentials.

#### Frontend configuration

- Enable/Disable Two-Factor Authentication
- Verification type (currently supports OTP)

#### Admin 2FA

Admin login can be protected with mandatory OTP verification independently of the frontend setting.
This is controlled via a DI parameter and is **disabled by default** to prevent accidental lockout.

**Option 1 — Environment variable** (recommended for container deployments):

```shell
OE_SECURITY_ADMIN_2FA_ENABLED=1
```

**Option 2 — DI parameter** (project-level `<shop-root>/var/configuration/configurable_services.yaml`):

```yaml
parameters:
  oe_security.admin_2fa_enabled: true
```

Either way, clear the container cache afterwards:

```shell
composer oe:container:reset
```

When enabled, every admin login requires OTP verification via a code sent to the admin's email address.
The feature can be disabled again by setting the parameter back to `false` and clearing the cache.

### Running the tests and quality tools

Check the "scripts" section in the `composer.json` file for the available commands. Those commands can be executed
by connecting to the php container and running the command from there, example:

```shell
make php
composer tests-coverage
```

Commands can be also triggered directly on the container with docker compose, example:

```shell
docker compose exec -T php composer tests-coverage
```

## Testing
### Linting, syntax check, static analysis

Check the "scripts" section in the `composer.json` file for the available commands. Those commands can be executed
by connecting to the php container and running the command from there, example:

```shell
make php
composer update
composer static
```

### Unit/Integration/Acceptance tests

- Run all the tests

```shell
composer tests-all
```

- Or the desired suite

```shell
composer tests-unit
composer tests-integration
composer tests-codeception
```

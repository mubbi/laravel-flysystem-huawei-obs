# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.0 (2025-07-31)


### Features

* created huawei obs flysystem adapter ([a6df483](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/a6df483d0c5f52860717635f03ece8f9d47cec0f))


### Bug Fixes

* commit linter action ([ac77876](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/ac778761fef4306ce846a2638f2097fb8a1bd973))
* github actions ([e5bd11f](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/e5bd11f401585e40a79014a35044a6066ec92664))
* permissions in action ([a574b28](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/a574b28f47998575f34fdf816ae4986781ed0b3d))
* release github action ([e06f188](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/e06f18886357ac9bd822a06362d2baaac033137c))
* release token access ([2264047](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/22640474215ef766f46067e316681d9676f7c2c3))
* remove extra action job ([24a6972](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/24a69729f48668850f23d5568e860918309ef518))
* write permissions for linter ([a5e8234](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/a5e82342f7e876a7384eac8f3e3a864d875afa5d))

## [1.0.0] - 2024-01-XX

### Added
- Initial release of Laravel Flysystem Huawei OBS adapter
- Full Flysystem v3 compatibility
- Complete OBS operations support (upload, download, delete, copy, move)
- Directory operations (create, delete, list contents)
- File visibility management (public/private)
- Metadata retrieval (file size, last modified, mime type)
- Prefix support for multi-tenant applications
- Custom HTTP client configuration
- **Temporary credentials (Security Token) support**
- **Signed URLs for temporary access to objects**
- **Post signatures for direct browser uploads**
- **Object tagging (metadata) operations**
- **Object restoration for archived objects**
- **Credential refresh for long-running applications**
- Comprehensive test coverage with PHPUnit
- PHPStan level 8 static analysis
- Laravel Pint code style compliance
- GitHub Actions CI/CD pipeline
- Complete documentation with usage examples

### Technical Features
- Laravel 11+ compatibility
- PHP 8.2+ requirement
- Uses official `obs/esdk-obs-php` SDK
- Proper exception handling with Flysystem exceptions
- Type-safe implementation with strict typing
- PSR-4 autoloading
- Laravel service provider auto-discovery

### Configuration Options
- Access key ID and secret access key
- Bucket name and endpoint configuration
- Optional prefix for multi-tenant support
- Security token for temporary credentials
- Custom HTTP client settings (timeout, proxy, headers)
- File visibility defaults
- Error handling configuration

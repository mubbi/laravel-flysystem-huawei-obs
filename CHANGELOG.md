# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
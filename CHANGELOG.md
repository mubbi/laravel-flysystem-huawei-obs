# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2025-01-27

### Added
- **Multi-Version Support**: Added support for Guzzle v6, v7, and v8 with automatic detection
- **Flysystem Compatibility**: Maintained compatibility with Flysystem v2 through dependency constraints
- **Abstract Base Class**: Created `AbstractHuaweiObsAdapter` to share common functionality
- **HTTP Client Factory**: Added `HttpClientFactory` to handle different Guzzle versions
- **Compatibility Tests**: Added tests to verify multi-version support works correctly

### Changed
- **Dependencies**: Updated `composer.json` to support multiple versions:
  - `league/flysystem`: `^2.0|^3.0` (v3 primary, v2 compatible)
  - `guzzlehttp/guzzle`: `^6.3.0|^7.0|^8.0`
- **Service Provider**: Updated to use HTTP client factory for version detection
- **Documentation**: Updated README with multi-version compatibility information

### Technical Details
- **Flysystem**: Uses v3 as primary interface with v2 compatibility through constraints
- **Guzzle v6**: Uses v6-specific client configurations
- **Guzzle v7/v8**: Uses modern client configurations
- **Backward Compatibility**: All existing functionality remains unchanged

### Breaking Changes
- None. This is a fully backward compatible release.

## [1.1.0] - 2024-12-15

### Added
- **Enhanced Error Handling**: Improved error messages and exception handling
- **Authentication Validation**: Proactive checking of OBS credentials and bucket access
- **Retry Logic**: Automatic retry with exponential backoff for transient errors
- **Authentication Caching**: Cache authentication status to improve performance
- **Logging Support**: Optional operation and error logging
- **Artisan Commands**: Built-in testing command for connectivity verification
- **Type Safety**: PHPStan level 8 compliance with strict typing
- **Code Quality**: Laravel Pint compliance and PSR-12 standards

### Changed
- **Dependencies**: Updated to support Laravel 12
- **Documentation**: Enhanced README with comprehensive usage examples
- **Configuration**: Added more configuration options for HTTP client and logging

### Fixed
- **Security**: Fixed potential security issues with error message exposure
- **Performance**: Improved performance with authentication caching
- **Reliability**: Enhanced reliability with retry logic

## [1.0.1] - 2024-08-01

### Fixed
- **Laravel Version**: Fixed minimum Laravel version requirement in composer.json

## [1.0.0] - 2024-07-15

### Added
- **Initial Release**: Complete Flysystem v3 adapter for Huawei OBS
- **Core Features**: File operations, directory operations, metadata retrieval
- **Advanced Features**: Signed URLs, post signatures, object tagging, object restoration
- **Laravel Integration**: Seamless integration with Laravel's Storage facade
- **Security Features**: Authentication validation, secure defaults, temporary credentials
- **Configuration**: Comprehensive configuration options
- **Documentation**: Complete documentation with examples

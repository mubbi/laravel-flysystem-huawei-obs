# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.2](https://github.com/mubbi/laravel-flysystem-huawei-obs/compare/v1.1.1...v1.1.2) (2025-08-01)


### Bug Fixes

* add codecov back to pipeline ([65ebcf6](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/65ebcf6cbdb5baceec0a4f31da02ac25112a41d9))

## [1.1.1](https://github.com/mubbi/laravel-flysystem-huawei-obs/compare/v1.1.0...v1.1.1) (2025-08-01)


### Bug Fixes

* linting issues ([67a1113](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/67a1113ace20c32c90c6ace007cc87b7d39a93d6))
* phpstan analyze memory error ([7f5a35a](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/7f5a35afbb0e74669839263edafa4f36bbe5ad46))
* safe condition to prevent errors ([8581e9a](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/8581e9a77f7b2c64077af58987215219ed6ae7be))


### Miscellaneous Chores

* ignore coverage txt files ([4956ae1](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/4956ae11d8d0cee97f4beef489a887574cabd17d))
* remove cache file ([ad72df2](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/ad72df2b3627b6bdeccfb2ff440eb13051005159))

## [1.1.0](https://github.com/mubbi/laravel-flysystem-huawei-obs/compare/v1.0.1...v1.1.0) (2025-08-01)


### Features

* minimum requirements update ([f4bfc92](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/f4bfc92e71d87ac88c84497a71e67fbdcfa92352))
* minimum requirements update ([27a6986](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/27a698672460039a864505501962f3d5a82ec190))


### Bug Fixes

* ci for different versions ([4cdcf5f](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/4cdcf5fd1b61e60ebb70a2aa9bc69a70a076d8a3))
* ci pipeline ([4bb5fa0](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/4bb5fa0df93af6a3ec82255b147846354e31cd66))
* ci pipelines ([f81708c](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/f81708cae3e801c9d9ef39a3ccd8e74ec29ad772))
* ci pipelines ([afdec5a](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/afdec5a320fbf396579dce6c00ee4d7b96f08df1))
* versions support and ci ([e87c96d](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/e87c96dd4b0c9e2c9f911844d2588d8c9e5731a5))

## [1.0.1](https://github.com/mubbi/laravel-flysystem-huawei-obs/compare/v1.0.0...v1.0.1) (2025-08-01)


### Bug Fixes

* minimum laravel version requirement ([2e3a0b7](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/2e3a0b7140a7f3a9d3fb340ac26a90fbfb8d100e))
* minimum laravel version requirement ([26799a7](https://github.com/mubbi/laravel-flysystem-huawei-obs/commit/26799a7b7fc050611069011cbc570d23d59d68b8))

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
- Laravel 9.0+ compatibility (supports Laravel 9, 10, 11, and 12)
- PHP 8.1+ requirement
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

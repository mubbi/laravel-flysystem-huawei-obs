# Contributing to Laravel Flysystem Huawei OBS

Thank you for your interest in contributing to the Laravel Flysystem Huawei OBS adapter! This document provides guidelines and information for contributors.

## Table of Contents

- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Code Standards](#code-standards)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Issue Reporting](#issue-reporting)
- [Feature Requests](#feature-requests)
- [Documentation](#documentation)
- [Release Process](#release-process)

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git
- A Huawei Cloud OBS account (for testing)

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/laravel-flysystem-huawei-obs.git
   cd laravel-flysystem-huawei-obs
   ```

3. Add the upstream remote:
   ```bash
   git remote add upstream https://github.com/original-owner/laravel-flysystem-huawei-obs.git
   ```

## Development Setup

### 1. Install Dependencies

```bash
composer install
```

### 2. Environment Configuration

Copy the example environment file and configure your Huawei OBS credentials:

```bash
cp .env.example .env
```

Edit `.env` and add your Huawei OBS credentials:

```env
HUAWEI_OBS_ACCESS_KEY_ID=your_access_key_id
HUAWEI_OBS_SECRET_ACCESS_KEY=your_secret_access_key
HUAWEI_OBS_BUCKET=your_bucket_name
HUAWEI_OBS_ENDPOINT=https://obs.cn-north-1.myhuaweicloud.com
HUAWEI_OBS_REGION=cn-north-1
HUAWEI_OBS_PREFIX=
HUAWEI_OBS_SECURITY_TOKEN=your_security_token_for_temporary_credentials
```

### 3. Run Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test -- --coverage

# Run specific test file
composer test -- tests/HuaweiObsAdapterTest.php
```

### 4. Code Quality Checks

```bash
# Run all quality checks
composer check

# Run individual checks
composer phpstan
composer pint
```

## Code Standards

### PHP Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use strict typing (`declare(strict_types=1);`)
- Use type hints for all parameters and return types
- Use PHP 8.2+ features where appropriate

### Laravel Standards

- Follow Laravel's coding conventions
- Use Laravel's service provider patterns
- Follow Laravel's naming conventions

### Documentation Standards

- Use PHPDoc blocks for all public methods
- Include `@param`, `@return`, and `@throws` annotations
- Provide clear and concise descriptions

### Example Code Style

```php
<?php

declare(strict_types=1);

namespace LaravelFlysystemHuaweiObs;

use League\Flysystem\FilesystemAdapter;

/**
 * Example class demonstrating code standards.
 */
class ExampleClass
{
    /**
     * Example method with proper documentation.
     *
     * @param string $input The input string
     * @param array<string, mixed> $options Additional options
     * @return string The processed result
     * @throws \InvalidArgumentException If input is invalid
     */
    public function exampleMethod(string $input, array $options = []): string
    {
        if (empty($input)) {
            throw new \InvalidArgumentException('Input cannot be empty');
        }

        return strtoupper($input);
    }
}
```

## Testing

### Test Structure

- Tests should be in the `tests/` directory
- Use descriptive test method names
- Follow the pattern: `test_what_it_tests_when_condition()`
- Use Mockery for mocking OBS client
- Test both success and failure scenarios

### Writing Tests

```php
<?php

namespace LaravelFlysystemHuaweiObs\Tests;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_method_returns_expected_result_when_valid_input(): void
    {
        // Arrange
        $input = 'test';
        
        // Act
        $result = $this->method($input);
        
        // Assert
        $this->assertEquals('expected', $result);
    }

    public function test_method_throws_exception_when_invalid_input(): void
    {
        // Arrange
        $input = '';
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act
        $this->method($input);
    }
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run with verbose output
composer test -- --verbose

# Run specific test class
composer test -- --filter ExampleTest

# Run with coverage
composer test -- --coverage-html coverage/
```

## Pull Request Process

### 1. Create a Feature Branch

```bash
git checkout -b feature/your-feature-name
```

### 2. Make Your Changes

- Write your code following the standards above
- Add tests for new functionality
- Update documentation if needed
- Ensure all tests pass

### 3. Commit Your Changes

Use conventional commit messages:

```bash
git commit -m "feat: add new method for object tagging"
git commit -m "fix: handle authentication errors properly"
git commit -m "docs: update README with new features"
git commit -m "test: add tests for signed URL functionality"
```

#### Conventional Commits Guide

This project uses [Conventional Commits](https://www.conventionalcommits.org/) to automatically generate releases and changelogs. Your commit messages should follow this format:

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

##### Commit Types

| Type | Description | Version Bump | Example |
|------|-------------|--------------|---------|
| `feat` | A new feature | Minor | `feat: add support for object tagging` |
| `fix` | A bug fix | Patch | `fix: handle authentication errors properly` |
| `docs` | Documentation only changes | None | `docs: update README with new features` |
| `style` | Changes that do not affect the meaning of the code | None | `style: format code according to PSR-12` |
| `refactor` | A code change that neither fixes a bug nor adds a feature | None | `refactor: simplify authentication logic` |
| `perf` | A code change that improves performance | Patch | `perf: optimize file upload process` |
| `test` | Adding missing tests or correcting existing tests | None | `test: add tests for signed URL functionality` |
| `chore` | Changes to the build process or auxiliary tools | None | `chore: update dependencies` |
| `ci` | Changes to CI configuration files and scripts | None | `ci: add PHP 8.3 to test matrix` |
| `build` | Changes that affect the build system or external dependencies | None | `build: update composer dependencies` |
| `revert` | Reverts a previous commit | Patch | `revert: remove experimental feature` |

##### Breaking Changes

To indicate a breaking change, add `!` after the type/scope and `BREAKING CHANGE:` in the footer:

```
feat!: remove deprecated method

BREAKING CHANGE: The `oldMethod()` has been removed. Use `newMethod()` instead.
```

This will trigger a **major version bump**.

##### Commit Scopes (Optional)

You can specify a scope to indicate which part of the codebase is affected:

```
feat(adapter): add support for object tagging
fix(service-provider): handle missing configuration
docs(readme): update installation instructions
test(adapter): add tests for file operations
```

Common scopes for this project:
- `adapter` - Changes to the main adapter class
- `service-provider` - Changes to the service provider
- `exceptions` - Changes to exception classes
- `config` - Changes to configuration files
- `readme` - Changes to README documentation
- `ci` - Changes to CI/CD configuration

##### Commit Examples

**Feature (Minor version bump):**
```
feat: add support for temporary signed URLs

- Add generateTemporaryUrl method
- Support for custom expiration times
- Handle URL signing errors gracefully
```

**Bug Fix (Patch version bump):**
```
fix(adapter): handle empty bucket name error

Closes #123
```

**Breaking Change (Major version bump):**
```
feat!: rename config key from 'obs' to 'huawei-obs'

BREAKING CHANGE: The configuration key has been renamed from 'obs' to 'huawei-obs' 
for better clarity. Update your config/filesystems.php accordingly.
```

**Documentation (No version bump):**
```
docs: update installation guide with new requirements

- Add PHP 8.2+ requirement
- Update Laravel version compatibility
- Add troubleshooting section
```

**Multiple Issues:**
```
fix: resolve authentication and timeout issues

- Fix OBS client authentication
- Add proper timeout handling
- Improve error messages

Closes #123
Fixes #124
```

##### Commit Message Guidelines

1. **Use imperative mood**: "add" not "added" or "adds"
2. **Don't capitalize the first letter**: "feat: add" not "feat: Add"
3. **No period at the end**: "feat: add feature" not "feat: add feature."
4. **Keep it under 72 characters**: If longer, use the body
5. **Be specific**: "fix: handle null bucket name" not "fix: bug fix"

##### Automatic Release Process

When you push commits with conventional commit messages to the `main` branch:

1. **Release-please analyzes** your commits
2. **Determines version bump** based on commit types
3. **Creates a Release PR** with version updates
4. **When merged**, automatically:
   - Creates a Git tag (e.g., `v1.1.0`)
   - Creates a GitHub release
   - Updates `CHANGELOG.md`
   - Updates version in `composer.json`

##### Version Bump Rules

- **Major (1.0.0 → 2.0.0)**: Breaking changes (`feat!`, `fix!`)
- **Minor (1.0.0 → 1.1.0)**: New features (`feat`)
- **Patch (1.0.0 → 1.0.1)**: Bug fixes (`fix`, `perf`)
- **None**: Documentation, style, refactor, test, chore, ci, build

##### Tips for Good Commit Messages

1. **Separate concerns**: One logical change per commit
2. **Write clear descriptions**: Explain what and why, not how
3. **Reference issues**: Use "Closes #123" or "Fixes #456"
4. **Use scopes**: When changes affect specific components
5. **Test your commits**: Ensure they trigger the right version bump

##### Commit Validation

The CI pipeline automatically validates all commit messages:

- **Pull Requests**: Validates the PR title follows conventional commit format
- **Direct Pushes**: Validates all commit messages in the push

This ensures all commits follow the conventional commit format for proper release automation.

### 4. Push and Create Pull Request

```bash
git push origin feature/your-feature-name
```

Then create a pull request on GitHub with:

- Clear title describing the change
- Detailed description of what was changed and why
- Reference to any related issues
- Screenshots if UI changes are involved

### 5. Pull Request Checklist

Before submitting, ensure:

- [ ] All tests pass
- [ ] Code follows PSR-12 standards
- [ ] PHPStan analysis passes (level 8)
- [ ] Laravel Pint formatting is applied
- [ ] Documentation is updated
- [ ] New features have tests
- [ ] Breaking changes are documented

## Issue Reporting

### Bug Reports

When reporting bugs, please include:

1. **Environment Information**:
   - PHP version
   - Laravel version
   - Package version
   - Operating system

2. **Steps to Reproduce**:
   - Clear, step-by-step instructions
   - Minimal code example

3. **Expected vs Actual Behavior**:
   - What you expected to happen
   - What actually happened

4. **Error Messages**:
   - Full error messages and stack traces
   - Log files if applicable

### Example Bug Report

```markdown
## Bug Report

**Environment:**
- PHP: 8.3.0
- Laravel: 11.0
- Package: 1.0.0
- OS: Ubuntu 22.04

**Steps to Reproduce:**
1. Configure Huawei OBS with invalid credentials
2. Call `Storage::disk('huawei-obs')->exists('test.txt')`
3. Observe error

**Expected Behavior:**
Should return `false` or throw a clear authentication error

**Actual Behavior:**
Throws generic "Unable to check file existence" error

**Error Message:**
```
League\Flysystem\UnableToCheckFileExistence: Unable to check file existence for location: test.txt
```

**Code Example:**
```php
Storage::disk('huawei-obs')->exists('test.txt');
```
```

## Feature Requests

When requesting features, please include:

1. **Use Case**: Why do you need this feature?
2. **Proposed Solution**: How should it work?
3. **Alternatives**: What have you tried?
4. **Impact**: Who will benefit from this?

## Documentation

### Documentation Standards

- Use clear, concise language
- Include code examples
- Provide both basic and advanced usage
- Keep documentation up to date with code changes

### Documentation Types

1. **README.md**: Main documentation
2. **CHANGELOG.md**: Version history
3. **SECURITY.md**: Security policy
4. **CODE_OF_CONDUCT.md**: Community guidelines
5. **API Documentation**: PHPDoc comments

## Release Process

### Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Checklist

Before releasing:

- [ ] All tests pass
- [ ] Documentation is updated
- [ ] CHANGELOG.md is updated
- [ ] Version is bumped in composer.json
- [ ] Tag is created and pushed
- [ ] Release notes are written

### Creating a Release

```bash
# Update version in composer.json
# Update CHANGELOG.md
git add .
git commit -m "chore: prepare release v1.1.0"
git tag v1.1.0
git push origin main --tags
```

## Getting Help

If you need help with contributing:

- Check existing issues and pull requests
- Join our community discussions
- Contact maintainers directly
- Read the documentation thoroughly

## Recognition

Contributors will be recognized in:

- README.md contributors section
- Release notes
- GitHub contributors page
- Community acknowledgments

Thank you for contributing to the Laravel Flysystem Huawei OBS adapter! 
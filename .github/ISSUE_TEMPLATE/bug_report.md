---
name: Bug report
about: Create a report to help us improve
title: '[BUG] '
labels: ['bug']
assignees: ''
---

## Bug Description

A clear and concise description of what the bug is.

## Environment

**PHP Version:** 
**Laravel Version:** 
**Package Version:** 
**Operating System:** 
**Huawei OBS SDK Version:** 

## Steps to Reproduce

1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

## Expected Behavior

A clear and concise description of what you expected to happen.

## Actual Behavior

A clear and concise description of what actually happened.

## Error Messages

```
Paste any error messages or stack traces here
```

## Code Example

```php
// Minimal code example that reproduces the issue
Storage::disk('huawei-obs')->put('test.txt', 'content');
```

## Configuration

```php
// Your filesystem configuration (remove sensitive data)
'disks' => [
    'huawei-obs' => [
        'driver' => 'huawei-obs',
        'key' => '***',
        'secret' => '***',
        'bucket' => 'your-bucket',
        'endpoint' => 'https://obs.cn-north-1.myhuaweicloud.com',
        // ... other config
    ],
],
```

## Additional Context

Add any other context about the problem here, such as:
- When did this start happening?
- Is this a regression from a previous version?
- Are there any workarounds you've found?

## Checklist

- [ ] I have searched existing issues to avoid duplicates
- [ ] I have provided all required information
- [ ] I have tested with the latest version
- [ ] I have included a minimal reproduction example 
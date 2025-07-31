# Security Policy

## Supported Versions

We actively maintain and provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security vulnerability in this package, please follow these steps:

### 1. **DO NOT** create a public GitHub issue
Security vulnerabilities should be reported privately to prevent potential exploitation.

### 2. Email the security team
Send an email to: [hello@mubbi.me](mailto:hello@mubbi.me)

### 3. Include the following information
- **Description**: A clear description of the vulnerability
- **Steps to reproduce**: Detailed steps to reproduce the issue
- **Impact**: Potential impact of the vulnerability
- **Suggested fix**: If you have a suggested fix (optional)
- **Affected versions**: Which versions are affected
- **Proof of concept**: If applicable, include a proof of concept

### 4. Response timeline
- **Initial response**: Within 48 hours
- **Status update**: Within 7 days
- **Fix timeline**: Depends on severity and complexity

## Security Best Practices

### Credential Management

1. **Never commit credentials to version control**
   ```bash
   # ❌ Never do this
   HUAWEI_OBS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
   HUAWEI_OBS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
   
   # ✅ Use environment variables
   HUAWEI_OBS_ACCESS_KEY_ID=${OBS_ACCESS_KEY}
   HUAWEI_OBS_SECRET_ACCESS_KEY=${OBS_SECRET_KEY}
   ```

2. **Use temporary credentials when possible**
   ```php
   // Use security tokens for temporary access
   $adapter = new HuaweiObsAdapter(
       'access_key_id',
       'secret_access_key',
       'bucket_name',
       'endpoint',
       null,
       null,
       'security_token_here' // Temporary credentials
   );
   ```

3. **Rotate credentials regularly**
   ```php
   // Refresh credentials during runtime
   $adapter->refreshCredentials('new_access_key', 'new_secret_key', 'new_security_token');
   ```

### Access Control

1. **Use least privilege principle**
   - Grant only necessary permissions to your OBS credentials
   - Use IAM policies to restrict access to specific buckets and operations

2. **Enable bucket encryption**
   ```php
   // Configure server-side encryption
   $options = [
       'Bucket' => $this->bucket,
       'Key' => $key,
       'Body' => $contents,
       'ServerSideEncryption' => 'AES256', // Enable encryption
   ];
   ```

3. **Use signed URLs for temporary access**
   ```php
   // Create temporary access URLs instead of making objects public
   $signedUrl = $adapter->createSignedUrl('sensitive-file.pdf', 'GET', 3600);
   ```

### Network Security

1. **Use HTTPS endpoints**
   ```php
   // Always use HTTPS
   'endpoint' => 'https://obs.cn-north-1.myhuaweicloud.com'
   ```

2. **Configure custom HTTP client with security settings**
   ```php
   $httpClient = new \GuzzleHttp\Client([
       'verify' => true, // Enable SSL verification
       'timeout' => 30,
       'connect_timeout' => 10,
   ]);
   ```

### Input Validation

1. **Validate file paths**
   ```php
   // Sanitize file paths to prevent path traversal
   $path = filter_var($path, FILTER_SANITIZE_STRING);
   ```

2. **Validate file types and sizes**
   ```php
   // Check file types before upload
   $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
   if (!in_array($mimeType, $allowedTypes)) {
       throw new \InvalidArgumentException('File type not allowed');
   }
   ```

### Error Handling

1. **Don't expose sensitive information in error messages**
   ```php
   // ❌ Don't expose internal details
   throw new \Exception('Failed to connect to OBS with key: ' . $accessKey);
   
   // ✅ Use generic error messages
   throw new \Exception('Failed to connect to storage service');
   ```

2. **Log security events**
   ```php
   // Log authentication failures
   if ($e->getExceptionCode() === 'AccessDenied') {
       Log::warning('OBS authentication failed', [
           'bucket' => $this->bucket,
           'ip' => request()->ip(),
       ]);
   }
   ```

## Security Features

This package includes several security features:

### 1. **Authentication Validation**
- Automatic authentication checks before operations
- Clear error messages for authentication failures
- Support for temporary credentials

### 2. **Secure Defaults**
- Private visibility by default
- SSL verification enabled
- Timeout configurations

### 3. **Input Sanitization**
- Path normalization
- Key validation
- Prefix handling

### 4. **Error Handling**
- Proper exception handling
- No sensitive data exposure
- Graceful degradation

## Compliance

This package is designed to help with various compliance requirements:

- **GDPR**: Data encryption and access controls
- **SOC 2**: Security controls and monitoring
- **HIPAA**: Secure data transmission and storage
- **PCI DSS**: Secure handling of sensitive data

## Security Updates

We regularly update dependencies and address security issues:

1. **Dependency updates**: Monthly security updates
2. **Vulnerability scanning**: Automated scanning in CI/CD
3. **Code review**: Security-focused code reviews
4. **Penetration testing**: Regular security assessments

## Contact

For security-related questions or concerns:

- **Security issues**: [hello@mubbi.me](mailto:hello@mubbi.me)
- **General support**: [GitHub Issues](https://github.com/mubbi/laravel-flysystem-huawei-obs/issues)
- **Documentation**: [README.md](README.md)
- **Author**: [Mubbasher Ahmed](https://mubbi.me)

## Acknowledgments

We thank security researchers and contributors who help improve the security of this package by responsibly reporting vulnerabilities. 
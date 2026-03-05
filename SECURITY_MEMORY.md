# Security Analysis Memory - Gyro PHP

## Found Vulnerabilities

### 1. CRITICAL: Insecure Token Generation (common.cls.php:287-299)
- `create_token()` uses `sha1(uniqid(mt_rand(), true))` - mt_rand() is NOT cryptographically secure
- `create_long_token()` uses `hash('sha3-256', uniqid(mt_rand(), true))` - same problem
- **Fix**: Use `random_bytes()` or `bin2hex(random_bytes(20))` for tokens

### 2. CRITICAL: Insecure Deserialization (dbfield.serialized.cls.php:43)
- `unserialize($value)` called on database results without `allowed_classes` restriction
- Also in cache implementations: cache.acpu.impl.php, cache.file.impl.php, cache.xcache.impl.php
- Also in sphinx driver: dbdriver.sphinx.php:205
- **Fix**: Use `unserialize($value, ['allowed_classes' => false])` or specific class list

### 3. HIGH: escape_database_entity SQL Injection (dbdriver.mysql.php:145-152)
- Database entity names (table/field) are wrapped in backticks but NOT escaped
- A backtick in the entity name can break out: `$obj = 'test` OR 1=1 --'`
- **Fix**: Strip or escape backticks in entity names

### 4. HIGH: Host Header Injection (requestinfo.cls.php:116-120)
- `HTTP_X_FORWARDED_HOST` is used directly without validation to construct URLs
- Allows host header injection attacks
- **Fix**: Validate against a whitelist or configured domain

### 5. HIGH: IP Spoofing via X-Forwarded-For (requestinfo.cls.php:165-183)
- `HTTP_X_FORWARDED_FOR` trusted by default for remote_address()
- Attacker can set arbitrary IP via this header
- **Fix**: Only trust when configured, or use rightmost IP

### 6. MEDIUM: Session Fixation Potential (session.cls.php:50,78,105-119)
- `start($id = false)` accepts session ID parameter
- `do_start_and_verify($id)` sets session ID from parameter
- If $id comes from user input, session fixation is possible
- Session regeneration exists for new sessions without ID but not after authentication

### 7. MEDIUM: Missing SameSite on Session Cookies (session.cls.php:89-99)
- Session cookies set without SameSite attribute
- **Fix**: Add SameSite=Lax to session cookie params

### 8. MEDIUM: Backtrace Header Information Disclosure (common.cls.php:86-94)
- `send_backtrace_as_headers()` exposes file paths, line numbers, function names
- Only gated by Config::TESTMODE but reveals internal paths

### 9. MEDIUM: ConverterHtmlEx missing escaping (htmlex.converter.php:22)
- Headings created without escaping: `html::tag('h' . $level, $text)`
- $text is NOT escaped (parent escapes in process_paragraph but child skips it)
- **Fix**: Add GyroString::escape() for the heading text

### 10. LOW: Deprecated session.bug_compat_42 (session.cls.php:5)
- `ini_set('session.bug_compat_42', 1)` - removed in PHP 5.4+

### 11. CRITICAL: Password hashing uses MD5/SHA1 (md5.hash.php, sha1.hash.php)
- MD5 and SHA1 used for password hashing - trivially crackable
- Timing attack: `==` comparison instead of `hash_equals()`
- Default hash type is 'md5'
- **Fix**: Added hash_equals(), created bcrypt.hash.php

### 12. HIGH: phpinfo() exposed without access control (phpinfo.controller.php)
- phpinfo() endpoint accessible without any auth or testmode check
- **Fix**: Added Config::TESTMODE check

### 13. MEDIUM: Missing security headers (pageviewbase.cls.php)
- No X-Content-Type-Options, X-Frame-Options, Referrer-Policy headers
- **Fix**: Added security headers

## Files to Modify
- gyro/core/lib/helpers/common.cls.php (token generation)
- gyro/core/model/base/fields/dbfield.serialized.cls.php (unserialize)
- gyro/core/model/drivers/mysql/dbdriver.mysql.php (escape_database_entity)
- gyro/core/lib/helpers/requestinfo.cls.php (host header injection, IP spoofing)
- gyro/core/lib/helpers/session.cls.php (session security)
- contributions/cache.acpu/cache.acpu.impl.php (unserialize)
- contributions/cache.file/cache.file.impl.php (unserialize)
- contributions/cache.xcache/cache.xcache.impl.php (unserialize)
- contributions/sphinx/model/drivers/sphinx/dbdriver.sphinx.php (unserialize)
- contributions/text.htmlpurifier/3rdparty/... (eval - but 3rdparty, skip)
- gyro/core/lib/helpers/converters/htmlex.converter.php (XSS in headings)

## Status: Starting fixes

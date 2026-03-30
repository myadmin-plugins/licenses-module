---
name: write-phpunit-test
description: Creates PHPUnit 9 tests in `tests/` using the ReflectionFunction/ReflectionClass pattern from `tests/ApiTest.php` and `tests/PluginTest.php`. Covers function existence, parameter names, parameter counts, optionality, and default values. Use when user says 'write test', 'add test for', 'test the API function', or adds a new function to src/api.php. Do NOT use for integration tests that require a live database, real MyAdmin global functions, or HTTP calls.
---
# write-phpunit-test

## Critical

- **Never call the functions under test** — they depend on `get_module_db()`, `get_module_settings()`, and other MyAdmin globals unavailable in tests. Use `ReflectionFunction` / `ReflectionClass` for structural validation only.
- **All test files must declare** `declare(strict_types=1);` and use namespace `Detain\MyAdminLicenses\Tests`.
- **Bootstrap defines constants** `NORMAL_BILLING` and `MYSQL_ASSOC` before including `src/api.php` — never redefine them in tests.
- **Do not add new bootstrap entries** unless a new constant is required by `src/api.php` at parse time.
- Run `composer test` after every addition to confirm zero failures and zero warnings (`failOnWarning="true"` is set).

## Instructions

### Step 1 — Read the target source file

Before writing tests, read the function or class you are testing:
- For API functions: read `src/api.php`
- For the Plugin class: read `src/Plugin.php`

Record every function name, its parameter names in order, which parameters have defaults, and the default values.

Verify the file exists at `dirname(__DIR__) . '/src/api.php'` (relative to `tests/`) before proceeding.

### Step 2 — Choose the correct test file

- New `src/api.php` functions → add methods to `tests/ApiTest.php`
- New `Plugin` methods or properties → add methods to `tests/PluginTest.php`
- A wholly new source file → create a new test file in `tests/` following the same skeleton (see Examples)

Verify the target test file exists before editing it. If creating a new file, confirm `tests/` exists.

### Step 3 — Write existence tests

For each new global function, add one `testApiFunctionNameExists` method:

```php
public function testApiCancelLicenseExists(): void
{
    $this->assertTrue(function_exists('api_cancel_license'));
}
```

For a new source file, also add `testApiFileExists` and `testApiFileStartsWithPhpTag`:

```php
public function testApiFileExists(): void
{
    $path = dirname(__DIR__) . '/src/api.php';
    $this->assertFileExists($path);
    $this->assertFileIsReadable($path);
}

public function testApiFileStartsWithPhpTag(): void
{
    $path = dirname(__DIR__) . '/src/api.php';
    $contents = file_get_contents($path);
    $this->assertNotFalse($contents);
    $this->assertStringStartsWith('<?php', $contents);
}
```

### Step 4 — Write parameter count tests

```php
public function testApiCancelLicenseParameterCount(): void
{
    $ref = new ReflectionFunction('api_cancel_license');
    $this->assertCount(2, $ref->getParameters());
}
```

For functions with zero parameters:

```php
public function testApiGetLicenseTypesHasNoParameters(): void
{
    $ref = new ReflectionFunction('api_get_license_types');
    $this->assertCount(0, $ref->getParameters());
}
```

### Step 5 — Write parameter name tests

```php
public function testApiCancelLicenseParameterNames(): void
{
    $ref = new ReflectionFunction('api_cancel_license');
    $params = $ref->getParameters();
    $this->assertSame('sid', $params[0]->getName());
    $this->assertSame('id', $params[1]->getName());
}
```

`$sid` must always be `$params[0]` for functions that take it — the `testSidIsFirstParameterWhereApplicable` batch test already covers all existing functions; add new ones to its `$functionsWithSid` array.

### Step 6 — Write optionality and default value tests

For required-only parameters:

```php
public function testApiCancelLicenseAllParamsRequired(): void
{
    $ref = new ReflectionFunction('api_cancel_license');
    foreach ($ref->getParameters() as $param) {
        $this->assertFalse($param->isOptional(), "Parameter \${$param->getName()} should be required");
    }
}
```

For mixed required/optional (test each index explicitly):

```php
public function testApiBuyLicenseOptionalParameters(): void
{
    $ref = new ReflectionFunction('api_buy_license');
    $params = $ref->getParameters();
    $this->assertFalse($params[0]->isOptional(), '$sid should be required');
    $this->assertFalse($params[2]->isOptional(), '$service_type should be required');
    $this->assertTrue($params[3]->isOptional(), '$coupon should be optional');
    $this->assertTrue($params[4]->isOptional(), '$use_prepay should be optional');
}
```

For each optional parameter, add a default value test:

```php
public function testApiBuyLicenseCouponDefaultValue(): void
{
    $ref = new ReflectionFunction('api_buy_license');
    $params = $ref->getParameters();
    $this->assertSame('', $params[3]->getDefaultValue());
}

public function testApiBuyLicenseUsePrepayDefaultValue(): void
{
    $ref = new ReflectionFunction('api_buy_license');
    $params = $ref->getParameters();
    $this->assertNull($params[4]->getDefaultValue());
}
```

### Step 7 — Update the batch/inventory tests

In `tests/ApiTest.php`, add any new function name to:
- `self::$apiFunctions` (the static list at the top of the class)
- `$functionsWithSid` inside `testSidIsFirstParameterWhereApplicable()` if the function accepts `$sid`

Verify `testAllFunctionsDefinedInSameFile` and `testAllFunctionsAreInGlobalNamespace` will still pass (all functions must live in `src/api.php`, global namespace).

### Step 8 — Source inspection tests (when function delegates or has notable structure)

For a wrapper/delegate function, assert the body calls the delegate:

```php
public function testApiBuyLicensePrepayDelegatesToBuyLicense(): void
{
    $ref = new ReflectionFunction('api_buy_license_prepay');
    $filename = $ref->getFileName();
    $this->assertNotFalse($filename);
    $source = file($filename);
    $this->assertNotFalse($source);
    $body = implode('', array_slice($source, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    $this->assertStringContainsString('api_buy_license(', $body);
}
```

For module-wide conventions, add source-level string assertions to confirm the file references the expected module, uses `get_module_db`, and uses `get_module_settings`:

```php
public function testApiFileReferencesLicensesModule(): void
{
    $contents = file_get_contents(dirname(__DIR__) . '/src/api.php');
    $this->assertNotFalse($contents);
    $this->assertStringContainsString("'licenses'", $contents);
}
```

### Step 9 — Run the suite

```bash
composer test
```

All tests must pass with zero errors, zero failures, zero warnings.

## Examples

**User says:** "Add a test for a new function `api_revoke_license($sid, $id, $reason = '')`"

**Actions taken:**
1. Read `src/api.php` to confirm `api_revoke_license` exists with those exact params.
2. Add to `self::$apiFunctions` in `tests/ApiTest.php`: `'api_revoke_license'`
3. Add to `$functionsWithSid`: `'api_revoke_license'`
4. Add four test methods:

```php
public function testApiRevokeLicenseExists(): void
{
    $this->assertTrue(function_exists('api_revoke_license'));
}

public function testApiRevokeLicenseParameterCount(): void
{
    $ref = new ReflectionFunction('api_revoke_license');
    $this->assertCount(3, $ref->getParameters());
}

public function testApiRevokeLicenseParameterNames(): void
{
    $ref = new ReflectionFunction('api_revoke_license');
    $params = $ref->getParameters();
    $this->assertSame('sid', $params[0]->getName());
    $this->assertSame('id', $params[1]->getName());
    $this->assertSame('reason', $params[2]->getName());
}

public function testApiRevokeLicenseOptionalParameters(): void
{
    $ref = new ReflectionFunction('api_revoke_license');
    $params = $ref->getParameters();
    $this->assertFalse($params[0]->isOptional(), '$sid should be required');
    $this->assertFalse($params[1]->isOptional(), '$id should be required');
    $this->assertTrue($params[2]->isOptional(), '$reason should be optional');
    $this->assertSame('', $params[2]->getDefaultValue());
}
```

5. Run `composer test` — all tests pass.

## Common Issues

**`Error: Call to undefined function get_module_db()`**  
You called the API function directly. Use `ReflectionFunction` only — never invoke `api_*` functions in tests.

**`ReflectionException: Function api_foo_bar() does not exist`**  
`src/api.php` was not included. Check `tests/bootstrap.php` has `require_once dirname(__DIR__) . '/src/api.php';`.

**`Constant MYSQL_ASSOC already defined`** or **`Constant NORMAL_BILLING already defined`**  
You defined a constant inside a test file. All constants must be defined only in `tests/bootstrap.php` with `if (!defined(...))` guards.

**`Failed asserting that 3 matches expected count 2`** on a parameter count test  
The function signature in `src/api.php` differs from what you assumed. Re-read `src/api.php` and count the actual parameters before writing the assertion.

**`PHPUnit\Framework\Warning: No tests found in class`**  
Test method does not start with `test`. Rename it from e.g. `apiExists()` to `testApiExists()`.

**`failOnWarning` causes a test run failure despite all tests passing**  
A `@todo` annotation or output statement exists in a test. Remove `@todo` annotations and `echo`/`var_dump` calls from all test methods.

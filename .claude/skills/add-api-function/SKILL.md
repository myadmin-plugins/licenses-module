---
name: add-api-function
description: Adds a new global-namespace API function to `src/api.php` following the existing pattern. Registers it in `Plugin::getRequirements()` and `Plugin::apiRegister()`. Use when user says 'add API function', 'new license operation', 'add endpoint to api.php', or adds a new license action. Do NOT use for modifying `src/Plugin.php` hooks unrelated to API registration.
---
# add-api-function

## Critical

- Functions MUST be in the global namespace — no `namespace` declaration in `src/api.php`
- `$sid` MUST always be the first parameter for any function that authenticates a user
- NEVER use PDO — always `$db = get_module_db('licenses')`
- ALWAYS pass `__LINE__, __FILE__` as the last two arguments to every `$db->query()` call
- ALWAYS escape user-supplied strings with `$db->real_escape()` before interpolating into queries
- ALWAYS cast integer inputs with `(int)` before use (e.g., `$id = (int)$id`)
- Every function that takes `$sid` MUST verify it with `$GLOBALS['tf']->session->verify()` and return early with `['status' => 'error', 'status_text' => 'Invalid Session ID']` on failure
- Every new function MUST be registered in both `getRequirements()` AND `apiRegister()` in `src/Plugin.php`
- Return type for all session-authenticated functions is `array` with at minimum `status` and `status_text` keys

## Instructions

### Step 1 — Write the function in `src/api.php`

Append the new function at the end of `src/api.php`. Follow this skeleton:

```php
/**
 * One-line description of what this does.
 *
 * @param string $sid        Session ID
 * @param int    $id         License Order ID
 * @return array
 * @throws \Exception
 */
function api_<verb>_license<_qualifier>($sid, $id)
{
    $id = (int)$id;   // cast ints immediately; skip for string params
    myadmin_log('api', 'info', "api_<verb>_license<_qualifier>('{$sid}', {$id}) called", __LINE__, __FILE__);
    $module = 'licenses';
    $db = get_module_db($module);
    $settings = get_module_settings($module);
    $return = [];
    $return['status'] = '';
    $return['status_text'] = '';
    $GLOBALS['tf']->session->sessionid = $sid;
    if ($GLOBALS['tf']->session->verify()) {
        $custid = get_custid($GLOBALS['tf']->session->account_id, $module);
        $GLOBALS['tf']->accounts->data = $GLOBALS['tf']->accounts->read($custid);
        $GLOBALS['tf']->ima = $GLOBALS['tf']->session->appsession('ima');
        $GLOBALS['tf']->session->update_dla();
    } else {
        $return['status'] = 'error';
        $return['status_text'] = 'Invalid Session ID';
        return $return;
    }
    update_session_log(__FUNCTION__);
    // --- your logic here ---
    $db->query("select * from {$settings['TABLE']} where {$settings['PREFIX']}_id='{$id}' and {$settings['PREFIX']}_custid='{$custid}'", __LINE__, __FILE__);
    if ($db->num_rows() == 0) {
        $return['status'] = 'error';
        $return['status_text'] = 'Invalid License ID';
        return $return;
    }
    $db->next_record(MYSQL_ASSOC);
    // operate on $db->Record ...
    $return['status'] = 'ok';
    $return['status_text'] = "Operation completed.\n";
    return $return;
}
```

For functions that do NOT authenticate (like `api_get_license_types`), omit the session block entirely.

Verify the function is at global scope (no wrapping namespace or class) before proceeding.

### Step 2 — Register in `getRequirements()` in `src/Plugin.php`

This step uses the function name from Step 1.

Inside `getRequirements(GenericEvent $event)`, add a line after the last existing `add_requirement` call:

```php
$loader->add_requirement('api_<verb>_license<_qualifier>', '/../vendor/detain/myadmin-licenses-module/src/api.php');
```

The path string is always `'/../vendor/detain/myadmin-licenses-module/src/api.php'` — do not change it.

Verify the new line is inside the `getRequirements` method body before proceeding.

### Step 3 — Register in `apiRegister()` in `src/Plugin.php`

This step uses the function name and parameter list from Step 1.

Inside `apiRegister(GenericEvent $event)`, add the return type definition and the function registration.

**3a.** If the return shape is new, define it with `api_register_array`:

```php
api_register_array('<verb>_license<_qualifier>_return', ['status' => 'string', 'status_text' => 'string']);
```

Add extra keys if the function returns more fields (e.g., `'invoice' => 'int'`, `'cost' => 'float'`).

**3b.** Register the function:

```php
api_register(
    'api_<verb>_license<_qualifier>',
    ['sid' => 'string', 'id' => 'int'],          // match actual params; omit $sid for unauthenticated
    ['return' => '<verb>_license<_qualifier>_return'],
    'Human-readable description of the operation.',
    true,   // true = requires authentication; false = public
    false
);
```

Param type map: PHP `string` → `'string'`, `int` → `'int'`, `float` → `'float'`, `bool`/`null` → `'boolean'`.

Verify both calls are inside the `apiRegister` method before proceeding.

### Step 4 — Add test coverage in `tests/ApiTest.php`

This step uses the function signature from Step 1.

Add to `$apiFunctions` array:
```php
'api_<verb>_license<_qualifier>',
```

Add existence and parameter tests following the existing pattern:

```php
public function testApi<VerbLicense>Exists(): void
{
    $this->assertTrue(function_exists('api_<verb>_license<_qualifier>'));
}

public function testApi<VerbLicense>ParameterCount(): void
{
    $ref = new ReflectionFunction('api_<verb>_license<_qualifier>');
    $this->assertCount(<N>, $ref->getParameters());
}

public function testApi<VerbLicense>ParameterNames(): void
{
    $ref = new ReflectionFunction('api_<verb>_license<_qualifier>');
    $params = $ref->getParameters();
    $this->assertSame('sid', $params[0]->getName());
    // add remaining params ...
}
```

### Step 5 — Verify

Run `vendor/bin/phpunit` from `/home/sites/mystage/vendor/detain/myadmin-licenses-module/`. All tests must pass before the work is done.

## Examples

**User says:** "Add an API function to suspend a license by ID"

**Actions taken:**

1. Append to `src/api.php`:
```php
/**
 * Suspends a Software License with id $id
 *
 * @param string $sid Session ID
 * @param int    $id  License Order ID
 * @return array
 * @throws \Exception
 */
function api_suspend_license($sid, $id)
{
    $id = (int)$id;
    myadmin_log('api', 'info', "api_suspend_license('{$sid}', {$id}) called", __LINE__, __FILE__);
    $module = 'licenses';
    $db = get_module_db($module);
    $settings = get_module_settings($module);
    $return = [];
    $return['status'] = '';
    $return['status_text'] = '';
    $GLOBALS['tf']->session->sessionid = $sid;
    if ($GLOBALS['tf']->session->verify()) {
        $custid = get_custid($GLOBALS['tf']->session->account_id, $module);
        $GLOBALS['tf']->accounts->data = $GLOBALS['tf']->accounts->read($custid);
        $GLOBALS['tf']->ima = $GLOBALS['tf']->session->appsession('ima');
        $GLOBALS['tf']->session->update_dla();
    } else {
        $return['status'] = 'error';
        $return['status_text'] = 'Invalid Session ID';
        return $return;
    }
    update_session_log(__FUNCTION__);
    $db->query("select * from {$settings['TABLE']} where {$settings['PREFIX']}_id='{$id}' and {$settings['PREFIX']}_custid='{$custid}'", __LINE__, __FILE__);
    if ($db->num_rows() == 0) {
        $return['status'] = 'error';
        $return['status_text'] = 'Invalid License ID';
        return $return;
    }
    $db->next_record(MYSQL_ASSOC);
    $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='suspended' where {$settings['PREFIX']}_id='{$id}'", __LINE__, __FILE__);
    $return['status'] = 'ok';
    $return['status_text'] = "License Suspended.\n";
    return $return;
}
```

2. In `src/Plugin.php` → `getRequirements()`:
```php
$loader->add_requirement('api_suspend_license', '/../vendor/detain/myadmin-licenses-module/src/api.php');
```

3. In `src/Plugin.php` → `apiRegister()`:
```php
api_register_array('suspend_license_return', ['status' => 'string', 'status_text' => 'string']);
api_register('api_suspend_license', ['sid' => 'string', 'id' => 'int'], ['return' => 'suspend_license_return'], 'Suspend a License.', true, false);
```

4. In `tests/ApiTest.php` add `'api_suspend_license'` to `$apiFunctions` and add existence/parameter tests.

**Result:** `vendor/bin/phpunit` passes with new tests for `api_suspend_license`.

## Common Issues

**`Fatal error: Cannot redeclare api_<name>()`**
The function already exists in `src/api.php`. Search with `grep -n 'function api_' src/api.php` and rename accordingly.

**`Call to undefined function get_module_db()`** during tests
This is expected — the bootstrap stubs out globals but does not define core MyAdmin helpers. Tests should use `function_exists()` and `ReflectionFunction`, not call the function directly.

**`$db->query()` missing `__LINE__, __FILE__`**
Every `$db->query(...)` call must end with `, __LINE__, __FILE__`. Omitting these will cause silent failures in the query log.

**Session block returns wrong keys**
Return array must initialize both `$return['status'] = ''` and `$return['status_text'] = ''` before the session check. Missing keys cause downstream code to emit PHP notices.

**`api_register` param types mismatch PHP signature**
If the PHP function uses `(int)$id` but `api_register` lists `'id' => 'string'`, the API schema will be wrong. Match PHP types to API type strings: `int` → `'int'`, `string` → `'string'`, `float` → `'float'`, `bool|null` → `'boolean'`.

**New function not showing in API**
Confirm both `getRequirements` (file loader) AND `apiRegister` (schema registration) were updated — missing either one means the function is either not loadable or not discoverable via the API schema.
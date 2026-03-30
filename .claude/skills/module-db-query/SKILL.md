---
name: module-db-query
description: Constructs DB queries for the licenses module using `get_module_db('licenses')` and `get_module_settings('licenses')` with proper PREFIX/TABLE interpolation. Use when querying or updating the licenses table, checking license status, or performing CRUD on license records. Trigger phrases: 'query licenses', 'look up license', 'update license status', 'insert license row', 'check if licensed'. Do NOT use PDO, do NOT hardcode table name 'licenses' or column prefix 'license_' directly — always use $settings['TABLE'] and $settings['PREFIX'].
---
# module-db-query

## Critical

- **Never use PDO** — only use `get_module_db()` throughout
- **Never hardcode** table name `licenses` or column prefix `license_` in query strings — always interpolate `{$settings['TABLE']}` and `{$settings['PREFIX']}`
- **Always pass `__LINE__, __FILE__`** as the 2nd and 3rd args to every `$db->query()` call
- **Always escape user input** with `$db->real_escape()` before interpolating into queries
- **Always cast numeric IDs** to `(int)` before use; never interpolate raw `$_GET`/`$_POST` values
- Use `make_insert_query()` for all INSERT statements — never build INSERT strings manually
- Validate ownership: verify `{$settings['PREFIX']}_custid` matches `$custid` before any mutation

## Instructions

### 1. Bootstrap the module context

Declare the module string once, then derive all other symbols from it:

```php
$module = 'licenses';
$settings = get_module_settings($module);  // keys: TABLE, PREFIX, TBLNAME, TITLE_FIELD, etc.
$db = get_module_db($module);
```

Verify `$settings['TABLE']` is `'licenses'` and `$settings['PREFIX']` is `'license'` before proceeding.

### 2. Escape string inputs immediately after obtaining the DB handle

```php
$ip    = $db->real_escape($ip);      // any string from external input
$coupon = $db->real_escape($coupon);
$id    = (int)$id;                   // numeric IDs — cast, not escaped
```

Verify: every variable interpolated into a query string was either cast to `int` or passed through `$db->real_escape()`.

### 3. SELECT — single expected row (lookup by ID)

```php
$db->query(
    "select * from {$settings['TABLE']}"
    ." where {$settings['PREFIX']}_id='{$id}'"
    ." and {$settings['PREFIX']}_custid='{$custid}'",
    __LINE__, __FILE__
);
if ($db->num_rows() == 0) {
    $return['status']      = 'error';
    $return['status_text'] = 'Invalid License ID';
    return $return;
}
$db->next_record(MYSQL_ASSOC);
$row = $db->Record;   // full row as assoc array
$licenseId = $row[$settings['PREFIX'].'_id'];
```

### 4. SELECT — multiple rows (loop)

```php
$db->query(
    "select * from {$settings['TABLE']}"
    ." where {$settings['PREFIX']}_ip='{$ip}'"
    ." and {$settings['PREFIX']}_custid='{$custid}'",
    __LINE__, __FILE__
);
if ($db->num_rows() > 0) {
    while ($db->next_record(MYSQL_ASSOC)) {
        $row = $db->Record;
        // use $row[$settings['PREFIX'].'_status'], etc.
    }
}
```

### 5. INSERT — use `make_insert_query()`

```php
$db->query(
    make_insert_query(
        $settings['TABLE'],
        [
            $settings['PREFIX'].'_id'         => null,
            $settings['PREFIX'].'_type'        => $service_type,
            $settings['PREFIX'].'_order_date'  => mysql_now(),
            $settings['PREFIX'].'_custid'      => $custid,
            $settings['PREFIX'].'_ip'          => $ip,
            $settings['PREFIX'].'_status'      => 'pending',
            $settings['PREFIX'].'_invoice'     => $rid,
            $settings['PREFIX'].'_coupon'      => $coupon_code,
            $settings['PREFIX'].'_extra'       => '',
            $settings['PREFIX'].'_hostname'    => '',
        ]
    ),
    __LINE__, __FILE__
);
$serviceid = $db->getLastInsertId($settings['TABLE'], $settings['PREFIX'].'_id');
```

Verify: `make_insert_query()` is called — no `INSERT INTO` string concatenation.

### 6. UPDATE — status change

```php
$db->query(
    "update {$settings['TABLE']}"
    ." set {$settings['PREFIX']}_status='active'"
    ." where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'",
    __LINE__, __FILE__
);
```

### 7. DELETE — by ID

```php
$db->query(
    "delete from {$settings['TABLE']}"
    ." where {$settings['PREFIX']}_id={$id}",
    __LINE__, __FILE__
);
```

### 8. Log significant operations

```php
myadmin_log('api', 'info', "Action on License {$serviceid} for Customer {$custid}", __LINE__, __FILE__);
```

## Examples

**User says:** "Look up a license by IP for the current customer and return its status."

**Actions taken:**

```php
$module   = 'licenses';
$settings = get_module_settings($module);
$db       = get_module_db($module);
$ip       = $db->real_escape($ip);   // escape before query

$db->query(
    "select * from {$settings['TABLE']}"
    ." where {$settings['PREFIX']}_ip='{$ip}'"
    ." and {$settings['PREFIX']}_custid='{$custid}'",
    __LINE__, __FILE__
);

if ($db->num_rows() == 0) {
    return ['status' => 'error', 'status_text' => 'No license found for that IP.'];
}
$db->next_record(MYSQL_ASSOC);
$status = $db->Record[$settings['PREFIX'].'_status'];
return ['status' => 'ok', 'license_status' => $status];
```

**Result:** Returns `['status' => 'ok', 'license_status' => 'active']` — or an error array if not found.

---

**User says:** "Insert a new pending license row after purchase."

**Actions taken:** Use Step 5 above. `make_insert_query()` builds the safe INSERT, `getLastInsertId()` retrieves the new ID.

## Common Issues

**`Call to undefined function get_module_db()`**
The function file isn't loaded yet. Add `function_requirements('get_module_db');` before calling it, or ensure you are inside a properly bootstrapped MyAdmin request context.

**`Call to undefined constant MYSQL_ASSOC`**
You are running outside the MyAdmin bootstrap. The constant is defined in the DB layer. In tests, ensure `tests/bootstrap.php` is loaded.

**Query returns 0 rows unexpectedly**
Check that `$custid` matches the session account. `get_custid($account_id, $module)` may differ from `$session->account_id` — use `get_custid()` or `convert_custid()` as other api functions do.

**Column not found / wrong column name**
Never write `license_status` literally — always `{$settings['PREFIX']}_status`. If `$settings` is empty, `get_module_settings()` returned nothing: confirm the module string is exactly `'licenses'` (lowercase).

**Numeric ID treated as string in query**
If you forget `(int)$id`, a malicious `$id = "1' OR '1'='1"` bypasses the `real_escape()` on integer fields. Always cast: `$id = (int)$id;` immediately on receipt.

**`make_insert_query` not defined**
Call `function_requirements('make_insert_query');` first, or verify the MyAdmin core is loaded via `vendor/autoload.php` and the bootstrap.
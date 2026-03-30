# MyAdmin Licenses Module

Composer plugin package for the MyAdmin billing panel. Manages the full license lifecycle: purchase, IP assignment, IP change, cancellation, billing integration.

## Commands

```bash
composer install                        # install deps including phpunit
vendor/bin/phpunit                      # run all tests
```

## Structure

- **Plugin class**: `src/Plugin.php` — `Detain\MyAdminLicenses\Plugin`
- **API functions**: `src/api.php` — global namespace procedural functions
- **Tests**: `tests/ApiTest.php` · `tests/PluginTest.php` · `tests/bootstrap.php`
- **PHPUnit config**: `phpunit.xml.dist`
- **Autoload**: `Detain\MyAdminLicenses\` → `src/` · `Detain\MyAdminLicenses\Tests\` → `tests/`
- **CI/CD**: `.github/` — workflows for automated testing and deployment pipelines
- **IDE config**: `.idea/` — JetBrains IDE settings including inspectionProfiles, deployment.xml, and encodings.xml

## Plugin Architecture

`src/Plugin.php` registers hooks via `getHooks()` returning an array of `event => [class, method]` pairs:

```php
public static function getHooks() {
    return [
        'api.register'                        => [__CLASS__, 'apiRegister'],
        'function.requirements'               => [__CLASS__, 'getRequirements'],
        self::$module.'.load_processing'      => [__CLASS__, 'loadProcessing'],
        self::$module.'.settings'             => [__CLASS__, 'getSettings'],
    ];
}
```

**Module constants** (from `Plugin::$settings`):
- `$module = 'licenses'` · `PREFIX = 'license'` · `TABLE = 'licenses'` · `TBLNAME = 'Licenses'`
- `TITLE_FIELD = 'license_ip'` · `TITLE_FIELD2 = 'license_hostname'`

## API Functions (`src/api.php`)

All functions are global namespace, registered in `getRequirements()` and `apiRegister()`:

| Function | Parameters |
|---|---|
| `api_get_license_types()` | none |
| `api_buy_license($sid, $ip, $service_type, $coupon='', $use_prepay=null)` | purchase |
| `api_buy_license_prepay($sid, $ip, $service_type, $coupon='', $use_prepay=null)` | delegates to buy |
| `api_cancel_license($sid, $id)` | cancel by ID |
| `api_cancel_license_ip($sid, $ip, $service_type)` | cancel by IP |
| `api_change_license_ip($sid, $oldip, $newip)` | change by IP |
| `api_change_license_ip_by_id($sid, $id, $newip)` | change by ID |

`$sid` is always the first parameter where applicable.

## Database Pattern

```php
$settings = get_module_settings('licenses');  // returns PREFIX, TABLE, TBLNAME, etc.
$db = get_module_db('licenses');
$db->query("SELECT * FROM {$settings['TABLE']} WHERE {$settings['PREFIX']}_id='{$id}'", __LINE__, __FILE__);
$db->next_record(MYSQL_ASSOC);
$row = $db->Record;
```

Never use PDO. Always pass `__LINE__, __FILE__` to `$db->query()`. Escape user input with `$db->real_escape()`.

## Event Dispatch Pattern

```php
// In loadProcessing, handlers receive a GenericEvent
public static function loadProcessing(GenericEvent $event) {
    $service = $event->getSubject();
    $service->setModule(self::$module)
        ->setEnable(function ($service) {
            $settings = get_module_settings(self::$module);
            $db = get_module_db(self::$module);
            // ...
        })->register();
}
```

Email templates referenced: `email/admin/license_created.tpl` · `email/admin/license_reactivated.tpl` (fetched via `TFSmarty`).

## Testing Conventions

- `tests/bootstrap.php` defines `NORMAL_BILLING` and `MYSQL_ASSOC` constants, then requires `src/api.php`
- `tests/ApiTest.php` uses `ReflectionFunction` to assert parameter names, counts, and optionality
- `tests/PluginTest.php` covers `Plugin` class behavior
- All test classes extend `PHPUnit\Framework\TestCase` with `declare(strict_types=1)`
- Test namespace: `Detain\MyAdminLicenses\Tests`

## Conventions

- Indent with **tabs** (per `.scrutinizer.yml` coding style)
- `camelCase` for parameters and properties
- `UPPER_CASE` for constants
- No short open tags; one class per file
- `_('string')` for i18n strings in settings labels
- `myadmin_log('licenses', $level, $message, __LINE__, __FILE__)` for logging

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->

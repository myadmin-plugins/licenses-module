---
name: add-plugin-hook
description: Adds a new Symfony EventDispatcher hook to src/Plugin.php via getHooks(). Implements handler method receiving GenericEvent $event and calling $event->getSubject(). Use when user says 'add event hook', 'new plugin event', 'handle event', or extends plugin behavior. Do NOT use for adding api.php functions.
---
# Add Plugin Hook

## Critical

- Every hook entry in `getHooks()` MUST map to a `public static` method on `Plugin` — the framework validates this at runtime.
- Module-specific event keys MUST use `self::$module.'.event_name'` (e.g., `self::$module.'.suspend'`), not a hardcoded string.
- Global event keys (e.g., `'api.register'`, `'function.requirements'`) use plain string literals.
- The handler signature is always `public static function myHandler(GenericEvent $event)` — no other signature is valid.
- Never add `return` statements in hook handlers; they are fire-and-forget void methods.
- `use Symfony\Component\EventDispatcher\GenericEvent;` is already imported — do not duplicate it.

## Instructions

1. **Identify the event name and handler method name.**
   - For module-scoped events: key = `self::$module.'.your_event'` → method name e.g. `handleYourEvent`.
   - For global events: key = `'global.event_name'` → method name e.g. `onGlobalEventName`.
   - Verify the event name follows the dot-separated convention used by existing hooks in `src/Plugin.php` (e.g., `load_processing`, `settings`).

2. **Register the hook in `getHooks()` in `src/Plugin.php`.**
   Add the new entry to the returned array. This step uses the names determined in Step 1.
   ```php
   public static function getHooks()
   {
       return [
           'api.register'                          => [__CLASS__, 'apiRegister'],
           'function.requirements'                 => [__CLASS__, 'getRequirements'],
           self::$module.'.load_processing'        => [__CLASS__, 'loadProcessing'],
           self::$module.'.settings'               => [__CLASS__, 'getSettings'],
           self::$module.'.your_event'             => [__CLASS__, 'handleYourEvent'],  // NEW
       ];
   }
   ```
   Verify: the new key string appears in the array and `__CLASS__` is used (not the literal class name).

3. **Implement the handler method in `src/Plugin.php`.**
   Add the method after the last existing handler, before the closing `}` of the class.
   Always start by extracting the subject from the event:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function handleYourEvent(GenericEvent $event)
   {
       /**
        * @var \SomeExpectedType $subject
        **/
       $subject = $event->getSubject();
       $settings = get_module_settings(self::$module);
       $db = get_module_db(self::$module);
       // handler logic here
   }
   ```
   Verify: method is `public static`, parameter is typed `GenericEvent $event`, no `return` statement.

4. **Add a DB query if the handler reads/writes service data (optional).**
   Always pass `__LINE__, __FILE__` and escape user input:
   ```php
   $db->query(
       "SELECT * FROM {$settings['TABLE']} WHERE {$settings['PREFIX']}_id='" . $db->real_escape($id) . "'",
       __LINE__, __FILE__
   );
   $db->next_record(MYSQL_ASSOC);
   $row = $db->Record;
   ```
   Never use PDO. Never interpolate `$_GET`/`$_POST` directly — always `$db->real_escape()` first.

5. **Add a PHPUnit test in `tests/PluginTest.php`.**
   Follow the pattern of `testGetSettingsHookMapping()` and `testGetSettingsMethodSignature()`:
   ```php
   public function testHandleYourEventHookMapping(): void
   {
       $hooks = Plugin::getHooks();
       $this->assertSame([Plugin::class, 'handleYourEvent'], $hooks[Plugin::$module . '.your_event']);
   }

   public function testHandleYourEventMethodSignature(): void
   {
       $method = (new ReflectionClass(Plugin::class))->getMethod('handleYourEvent');
       $this->assertTrue($method->isStatic());
       $this->assertTrue($method->isPublic());
       $params = $method->getParameters();
       $this->assertCount(1, $params);
       $this->assertSame('event', $params[0]->getName());
       $this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
   }
   ```
   Verify: run `vendor/bin/phpunit tests/PluginTest.php` — all tests pass.

6. **Run the full test suite.**
   ```bash
   composer test
   ```
   All tests must pass before considering the hook complete.

## Examples

**User says:** "Add a hook to handle license suspension events."

**Actions taken:**

1. Event key: `self::$module.'.suspend'`, method: `handleSuspend`
2. Register in `getHooks()` in `src/Plugin.php`:
   ```php
   self::$module.'.suspend' => [__CLASS__, 'handleSuspend'],
   ```
3. Implement handler in `src/Plugin.php`:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function handleSuspend(GenericEvent $event)
   {
       /**
        * @var \ServiceHandler $service
        **/
       $service = $event->getSubject();
       $serviceInfo = $service->getServiceInfo();
       $settings = get_module_settings(self::$module);
       $db = get_module_db(self::$module);
       $db->query(
           "update {$settings['TABLE']} set {$settings['PREFIX']}_status='suspended' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'",
           __LINE__, __FILE__
       );
       $GLOBALS['tf']->history->add(
           $settings['TABLE'], 'change_status', 'suspended',
           $serviceInfo[$settings['PREFIX'].'_id'],
           $serviceInfo[$settings['PREFIX'].'_custid']
       );
   }
   ```
4. Add tests for mapping and signature in `tests/PluginTest.php`, run `composer test`.

**Result:** `getHooks()` returns 5 entries; `licenses.suspend` dispatches to `Plugin::handleSuspend`.

## Common Issues

- **`Call to undefined method` at runtime:** The method name in `getHooks()` doesn't match the actual method name — check spelling exactly. Both must match (PHP method names are case-insensitive but keep them consistent).

- **Test `testGetHooksReturnsFourHooks` fails after adding hook:** That test asserts exactly 4 hooks. Update it to the new count (`assertCount(5, $hooks)`) or replace it with a `assertGreaterThanOrEqual` check.

- **`$event->getSubject()` returns unexpected type:** The subject type depends on which system dispatches the event. Check what `run_event('licenses.your_event', $data, 'licenses')` passes as the subject in the caller — `loadProcessing` receives a `\ServiceHandler`, `getSettings` receives `\MyAdmin\Settings`.

- **Hook never fires:** Confirm the event name string exactly matches what the dispatcher uses. Module-specific hooks must be `licenses.your_event` (using the value of `$module = 'licenses'`), not `license.your_event`.

- **`composer test` reports parse error after edit:** Trailing comma missing after new hook entry in the array — PHP 7.4+ allows trailing commas in arrays; add one after the last entry to avoid merge conflicts later.

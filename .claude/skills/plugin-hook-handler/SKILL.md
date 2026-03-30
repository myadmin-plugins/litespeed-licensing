---
name: plugin-hook-handler
description: Adds a new static hook handler method to `src/Plugin.php` and registers it in `getHooks()`. Use when user says 'add hook', 'new handler', 'register event', 'add event listener', or when modifying `src/Plugin.php`. Key capabilities: GenericEvent pattern, get_service_define guard, stopPropagation, myadmin_log. Do NOT use for modifying procedural functions in `src/litespeed.inc.php` or `src/litespeed_list.php`.
---
# plugin-hook-handler

## Critical

- Every handler that is service-specific **must** guard with `if ($event['category'] == get_service_define('LITESPEED'))` before doing any work.
- Always call `$event->stopPropagation()` as the **last statement inside** the category guard block — never outside it.
- Never call `$event->stopPropagation()` in `getMenu`, `getRequirements`, or `getSettings` — those hooks are not category-guarded.
- All handlers must be `public static function` accepting exactly `GenericEvent $event`.
- Every new hook key must be registered in `getHooks()` before the handler will fire.
- Do **not** add business logic to `src/Plugin.php` — delegate to functions in `src/litespeed.inc.php` loaded via `function_requirements()`.

## Instructions

### Step 1 — Identify the hook event name

Determine the Symfony event string you need to listen to. Existing event names follow the pattern `{module}.{action}`, e.g.:
- `licenses.activate`, `licenses.reactivate`
- `licenses.deactivate`, `licenses.deactivate_ip`
- `licenses.change_ip`
- `function.requirements`, `licenses.settings`

Verify the event name is not already registered in `getHooks()` before proceeding.

### Step 2 — Register the hook in `getHooks()`

Open `src/Plugin.php`. Inside the `getHooks()` return array, add a new entry:

```php
self::$module.'.your_action' => [__CLASS__, 'getYourAction'],
```

If the same handler should fire for multiple events (like `activate`/`reactivate` share `getActivate`), add both keys pointing to the same method:

```php
self::$module.'.your_action' => [__CLASS__, 'getYourAction'],
self::$module.'.your_action_alt' => [__CLASS__, 'getYourAction'],
```

Verify the array entry is comma-separated and syntactically valid before continuing.

### Step 3 — Write the handler method

Add a new `public static function` below the last existing handler, before the closing `}` of the class. Use this exact skeleton:

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getYourAction(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('LITESPEED')) {
        myadmin_log(self::$module, 'info', 'LiteSpeed YourAction', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        // load any procedural helpers:
        function_requirements('your_litespeed_function');
        // call the helper:
        $response = your_litespeed_function($serviceClass->getIp(), $event['field1']);
        // set event success/response:
        $event['response'] = $response;
        if (/* success condition */) {
            $event['success'] = true;
        } else {
            $event['success'] = false;
        }
        $event->stopPropagation();
    }
}
```

For handlers that mutate service state (key, status), chain setters then `save()` exactly as in `getActivate()`:

```php
$serviceClass
    ->setKey($value)
    ->setExtra($value)
    ->save();
```

### Step 4 — Log every significant action

Use both logging calls for any API interaction:

```php
request_log('licenses', false, __FUNCTION__, 'litespeed', 'methodName', [$args], $response);
myadmin_log(self::$module, 'info', 'Descriptive message', __LINE__, __FILE__, self::$module, $serviceClass->getId());
```

Use `'error'` level instead of `'info'` when logging failure paths.

### Step 5 — Register any new procedural function dependencies

If Step 3 calls a new function from `src/litespeed.inc.php`, register it in `getRequirements()` in `src/Plugin.php`:

```php
$loader->add_requirement('your_litespeed_function', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php');
```

Verify the function name exactly matches what is defined in `src/litespeed.inc.php` before committing.

### Step 6 — Run tests

```bash
vendor/bin/phpunit tests/ -v
```

All tests in `tests/PluginTest.php`, `tests/FunctionSignatureTest.php`, and `tests/FileExistenceTest.php` must pass.

## Examples

**User says:** "Add a hook for `licenses.suspend` that calls `suspend_litespeed_new()` with the service key."

**Step 1** — Event name: `licenses.suspend`. Not in `getHooks()`. Proceed.

**Step 2** — Add to `getHooks()` in `src/Plugin.php`:
```php
self::$module.'.suspend' => [__CLASS__, 'getSuspend'],
```

**Step 3** — Add handler:
```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getSuspend(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('LITESPEED')) {
        myadmin_log(self::$module, 'info', 'LiteSpeed Suspend', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        function_requirements('suspend_litespeed_new');
        $response = suspend_litespeed_new($serviceClass->getKey());
        request_log('licenses', false, __FUNCTION__, 'litespeed', 'suspend_litespeed_new', [$serviceClass->getKey()], $response);
        $event['response'] = $response;
        if (isset($response['LiteSpeed_eService']['result']) && $response['LiteSpeed_eService']['result'] == 'success') {
            $event['success'] = true;
        } else {
            $event['success'] = false;
        }
        $event->stopPropagation();
    }
}
```

**Step 5** — In `getRequirements()` in `src/Plugin.php`:
```php
$loader->add_requirement('suspend_litespeed_new', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php');
```

**Result:** `licenses.suspend` fires → `getSuspend()` runs → calls `suspend_litespeed_new()` → sets `$event['success']` → stops propagation.

## Common Issues

**Hook fires but handler never executes:**
- Check `getHooks()` key exactly matches the dispatched event string (e.g., `licenses.suspend` not `license.suspend`).
- Verify `[__CLASS__, 'getYourAction']` matches the method name exactly — PHP method names are case-insensitive but typos cause silent misses.

**`get_service_define('LITESPEED')` returns null / condition never matches:**
- The define is registered via `licenses.settings`. Confirm the plugin is loaded and settings are bootstrapped before the event fires.
- In tests, mock or define `LITESPEED` manually: `define('LITESPEED', 99);`.

**`function_requirements('x')` throws "function not found":**
- The function must be declared in `src/litespeed.inc.php` AND registered in `getRequirements()` via `$loader->add_requirement('x', ...)`. Both are required.
- Verify the function name string passed to `function_requirements()` matches the PHP function name exactly.

**`$event->stopPropagation()` called but other handlers still run:**
- Ensure `stopPropagation()` is inside the `if ($event['category'] == ...)` block, not after it. Calling it unconditionally on the wrong category blocks other plugins.

**Tests fail with "Call to undefined function myadmin_log":**
- The test bootstrap does not load procedural globals. Add a stub in `tests/` or check `tests/phpunit/prepend.php` in the parent project for the pattern used.

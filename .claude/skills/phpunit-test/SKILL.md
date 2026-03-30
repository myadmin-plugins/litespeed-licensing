---
name: phpunit-test
description: Writes PHPUnit 9 tests in `tests/` under `Detain\MyAdminLiteSpeed\Tests` namespace for the myadmin-litespeed-licensing plugin. Use when user says 'write test', 'add test coverage', 'add tests for X', or adds new functions/methods. Covers three test categories: Plugin class structure (ReflectionClass), procedural function signatures (ReflectionFunction), and file content assertions (string-contains). Do NOT use for integration tests requiring a real database, live LiteSpeed API calls, or tests that exercise actual activation/deactivation logic.
---
# PHPUnit Test Writing

## Critical

- **Never** execute `activate_litespeed*` / `deactivate_litespeed*` functions in tests — they call live LiteSpeed APIs and perform real provisioning.
- **Never** instantiate `\Detain\LiteSpeed\LiteSpeed` or `\Ganesh\LiteSpeed\LiteSpeedClient` in tests — no credentials in test env.
- All test files **must** declare `strict_types=1` and use `namespace Detain\MyAdminLiteSpeed\Tests`.
- PHPUnit config is `phpunit.xml.dist`. Run with: `phpunit`
- Tests live in `tests/` with `Test` suffix — PHPUnit picks them up automatically.
- `failOnRisky=true` and `beStrictAboutTestsThatDoNotTestAnything=true` are set — every test method **must** contain at least one assertion.

## Instructions

### 1. Choose the right test category

Three categories map to the three existing test files:

| Goal | Category | File pattern |
|---|---|---|
| Verify a PHP class structure, properties, methods | **Class structure** | using `ReflectionClass` |
| Verify a procedural function's signature/defaults | **Function signature** | using `ReflectionFunction` with global stubs |
| Verify file content (namespace, function declarations, strings) | **File content** | using `file_get_contents` + `assertStringContainsString` |

Verify which category fits before writing; most new tests will extend an existing file.

### 2. File header boilerplate

Every test file must start exactly as:

```php
<?php

declare(strict_types=1);

namespace Detain\MyAdminLiteSpeed\Tests;

use PHPUnit\Framework\TestCase;
```

Add additional `use` statements as needed (`ReflectionClass`, `ReflectionFunction`, `Symfony\Component\EventDispatcher\GenericEvent`).

### 3. Class structure tests (`ReflectionClass`)

Reference: `tests/PluginTest.php`

- Declare `private ReflectionClass $reflection` and initialize in `setUp()`:
  ```php
  protected function setUp(): void
  {
      $this->reflection = new ReflectionClass(Plugin::class);
  }
  ```
- Use `self::` (not `$this->`) for all assertions.
- Test pattern for verifying a static public method exists:
  ```php
  public function testMyMethodExists(): void
  {
      self::assertTrue($this->reflection->hasMethod('myMethod'));
      $m = $this->reflection->getMethod('myMethod');
      self::assertTrue($m->isStatic());
      self::assertTrue($m->isPublic());
  }
  ```
- Test pattern for verifying a static property value:
  ```php
  public function testModuleProperty(): void
  {
      self::assertSame('licenses', Plugin::$module);
  }
  ```
- Test return type with null fallback (not all methods are typed):
  ```php
  $returnType = $method->getReturnType();
  if ($returnType !== null) {
      self::assertSame('void', $returnType->getName());
  } else {
      self::assertNull($returnType);
  }
  ```

Verify the test runs without errors: `phpunit tests/PluginTest.php`

### 4. Procedural function signature tests (`ReflectionFunction`)

Reference: `tests/FunctionSignatureTest.php`

- Stub global functions and constants in `setUpBeforeClass()` inside a `function_exists` / `defined` guard:
  ```php
  public static function setUpBeforeClass(): void
  {
      if (!function_exists('myadmin_log')) {
          /** @return void */
          function myadmin_log(): void {}
      }
      if (!function_exists('request_log')) {
          /** @return void */
          function request_log(): void {}
      }
      if (!function_exists('chatNotify')) {
          /** @return void */
          function chatNotify(): void {}
      }
      if (!defined('LITESPEED_USERNAME')) {
          define('LITESPEED_USERNAME', 'test_user');
      }
      if (!defined('LITESPEED_PASSWORD')) {
          define('LITESPEED_PASSWORD', 'test_pass');
      }
  }
  ```
- Guard every test with `markTestSkipped` if the function isn't loaded:
  ```php
  if (!function_exists('my_function')) {
      self::markTestSkipped('my_function not loaded');
  }
  ```
- Assert parameter names by index:
  ```php
  $ref = new ReflectionFunction('activate_litespeed_new');
  $params = $ref->getParameters();
  self::assertSame('ipAddress', $params[0]->getName());
  ```
- Assert default values:
  ```php
  self::assertTrue($params[2]->isDefaultValueAvailable());
  self::assertSame('monthly', $params[2]->getDefaultValue());
  ```
- Assert total and required parameter counts:
  ```php
  self::assertSame(6, $ref->getNumberOfParameters());
  self::assertSame(2, $ref->getNumberOfRequiredParameters());
  ```

Verify: `phpunit tests/FunctionSignatureTest.php`

### 5. File content tests

Reference: `tests/FileExistenceTest.php`, `tests/LitespeedListTest.php`

- Use `dirname(__DIR__)` as the base path (resolves to the package root from `tests/`).
- Assert file existence before reading:
  ```php
  self::assertFileExists($this->basePath . '/src/Plugin.php');
  ```
- Assert content strings:
  ```php
  $content = file_get_contents($this->basePath . '/src/litespeed.inc.php');
  self::assertStringContainsString('function activate_litespeed(', $content);
  ```
- Assert regex for exact signatures:
  ```php
  self::assertMatchesRegularExpression('/function\s+litespeed_list\s*\(\s*\)/', $content);
  ```
- Assert namespace declaration:
  ```php
  self::assertStringContainsString('namespace Detain\\MyAdminLiteSpeed;', $content);
  ```

Verify: `phpunit tests/FileExistenceTest.php`

### 6. Run the full suite

```bash
phpunit
```

All tests must pass. If `failOnWarning` triggers, fix the PHP warning in the stub or production code — do not suppress it.

## Examples

**User says:** "Write tests for a new `getBalance` static method I added to `src/Plugin.php`"

**Actions taken:**
1. Open `tests/PluginTest.php` — it already has `$this->reflection` on `Plugin::class`.
2. Add two test methods to `PluginTest`:

```php
public function testGetBalanceMethodExists(): void
{
    self::assertTrue($this->reflection->hasMethod('getBalance'));
    $m = $this->reflection->getMethod('getBalance');
    self::assertTrue($m->isStatic());
    self::assertTrue($m->isPublic());
}

public function testGetBalanceMethodSignature(): void
{
    $method = $this->reflection->getMethod('getBalance');
    $params = $method->getParameters();
    self::assertCount(1, $params);
    self::assertSame('event', $params[0]->getName());
    $type = $params[0]->getType();
    self::assertNotNull($type);
    self::assertSame(GenericEvent::class, $type->getName());
}
```

3. Run `phpunit tests/PluginTest.php` — all pass.

**Result:** Two new tests verify the method is static, public, and accepts a `GenericEvent` — no API calls made.

---

**User says:** "Add tests for a new `get_litespeed_balance($serial)` function in `src/litespeed.inc.php`"

**Actions taken:**
1. Open `tests/FunctionSignatureTest.php` — stubs already defined in `setUpBeforeClass()`.
2. Add:

```php
public function testGetLitespeedBalanceSignature(): void
{
    if (!function_exists('get_litespeed_balance')) {
        self::markTestSkipped('get_litespeed_balance not loaded');
    }
    $ref = new ReflectionFunction('get_litespeed_balance');
    $params = $ref->getParameters();
    self::assertCount(1, $params);
    self::assertSame('serial', $params[0]->getName());
    self::assertFalse($params[0]->isDefaultValueAvailable());
    self::assertSame(1, $ref->getNumberOfRequiredParameters());
}
```

3. Add a file-content assertion in `tests/FileExistenceTest.php`:

```php
public function testLitespeedIncDeclaresGetBalanceFunction(): void
{
    $content = file_get_contents($this->basePath . '/src/litespeed.inc.php');
    self::assertStringContainsString('function get_litespeed_balance(', $content);
}
```

4. Run `phpunit` — all pass.

## Common Issues

**"This test did not perform any assertions"** → PHPUnit `beStrictAboutTestsThatDoNotTestAnything=true` is active. Add at least one `self::assert*()` call, or add `/** @doesNotPerformAssertions */` only if the test intentionally asserts nothing (rare).

**"Function X already defined"** → Global stubs in `setUpBeforeClass()` were defined without `function_exists()` guard. Wrap every stub: `if (!function_exists('myadmin_log')) { function myadmin_log(): void {} }`

**`ReflectionException: Function activate_litespeed does not exist`** → The `.inc.php` file was not required before the test. In `setUpBeforeClass()`, add: `require_once dirname(__DIR__) . '/src/litespeed.inc.php';` — but only after all stubs are defined.

**`Cannot redeclare constant LITESPEED_USERNAME`** → Wrap constant definitions: `if (!defined('LITESPEED_USERNAME')) { define('LITESPEED_USERNAME', 'test_user'); }`

**`TypeError: ... must be of type Symfony\Component\EventDispatcher\GenericEvent`** → You're calling an event handler method directly in a test. Don't — use `ReflectionMethod` to inspect the signature instead.

**`failOnWarning: test triggered a PHP warning`** → Usually a missing stub causes an undefined function notice. Add the stub to `setUpBeforeClass()` following the pattern in `tests/FunctionSignatureTest.php`.

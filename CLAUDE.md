# MyAdmin LiteSpeed Licensing Plugin

Composer plugin package for provisioning LiteSpeed Web Server and Load Balancer licenses inside the MyAdmin billing system.

## Commands

```bash
composer install                      # install deps including detain/litespeed-licensing
vendor/bin/phpunit                    # run all tests
```

## Architecture

**Namespace:** `Detain\MyAdminLiteSpeed\` → `src/` · **Tests:** `Detain\MyAdminLiteSpeed\Tests\` → `tests/`

**Key files:**
- `src/Plugin.php` — Symfony EventDispatcher hook registration; all hooks registered in `getHooks()`
- `src/litespeed.inc.php` — procedural activation/deactivation functions loaded via `function_requirements()`
- `src/litespeed_list.php` — admin UI page function `litespeed_list()` using `TFTable` + `add_output()`
- `bin/activate_litespeed.php` · `bin/test_litespeed_licenses.php` — manual CLI test scripts

**Hook lifecycle** (`src/Plugin.php::getHooks()`):
- `licenses.settings` → `getSettings()` — registers `LITESPEED_USERNAME`, `LITESPEED_PASSWORD`, `OUTOFSTOCK_LICENSES_LITESPEED` via `$settings->add_text_setting()` / `add_password_setting()` / `add_dropdown_setting()`
- `licenses.activate` / `licenses.reactivate` → `getActivate()` — calls `activate_litespeed_new()`; sets serial via `$serviceClass->setKey()->setExtra()->save()` or sets status `pending`
- `licenses.deactivate` / `licenses.deactivate_ip` → `getDeactivate()` — calls `deactivate_litespeed_new($serial)`
- `licenses.change_ip` → `getChangeIp()` — cancel old IP then re-activate; logs to `$GLOBALS['tf']->history`
- `function.requirements` → `getRequirements()` — registers `litespeed.inc.php` functions with `$loader->add_requirement()`
- `ui.menu` → `getMenu()` — adds admin link for `choice=none.litespeed_list`

**LiteSpeed API clients** (two generations in `src/litespeed.inc.php`):
- Legacy: `new \Detain\LiteSpeed\LiteSpeed(LITESPEED_USERNAME, LITESPEED_PASSWORD)` — `order()`, `cancel()`
- Current: `new \Ganesh\LiteSpeed\LiteSpeedClient(LITESPEED_USERNAME, LITESPEED_PASSWORD, true)` — `order()`, `cancel()`, `getLicenseDetails()`, `getBalance()`

**Activation guards** in `activate_litespeed_new()`:
1. Duplicate check via `getLicenseDetails('IP', $ipAddress)` when `$lic_check=true`
2. Credit balance check via `getBalance()` — abort if `credit <= 5.00` (except `WS_F` product)
3. Sends admin error email via `(new \MyAdmin\Mail())->adminMail($subject, $body, false, 'admin/licenses_error.tpl')` on failure

**Logging pattern** (use everywhere):
```php
request_log('licenses', false, __FUNCTION__, 'litespeed', 'methodName', [$args], $response);
myadmin_log('licenses', 'info', "Message text", __LINE__, __FILE__);
```

**Email on error** (deactivation failure):
```php
$smartyE = new TFSmarty();
$smartyE->assign('h1', 'LiteSpeed License Deactivation');
$smartyE->assign('body_rows', $bodyRows);
$msg = $smartyE->fetch('email/client/client_email.tpl');
(new \MyAdmin\Mail())->adminMail($subject, $msg, false, 'client/client_email.tpl');
```

## Testing

Test suite in `tests/` with PHPUnit 9 (config `phpunit.xml.dist`):
- `tests/FileExistenceTest.php` — asserts `src/Plugin.php`, `src/litespeed.inc.php`, `src/litespeed_list.php`, `composer.json`, `README.md` exist and contain expected symbols
- `tests/FunctionSignatureTest.php` — uses `ReflectionFunction` to assert parameter names/defaults for `activate_litespeed`, `activate_litespeed_new`, `deactivate_litespeed`, `deactivate_litespeed_new`
- `tests/LitespeedListTest.php` — string-asserts `src/litespeed_list.php` uses `get_module_settings('licenses')`, `TFTable`, `add_output()`
- `tests/PluginTest.php` — uses `ReflectionClass` on `Plugin::class`; asserts all 8 hook keys, static properties `$name/$module/$type`, method signatures accepting `GenericEvent`

Test bootstrap stubs missing globals: define `myadmin_log()`, `request_log()`, `chatNotify()`, `LITESPEED_USERNAME`, `LITESPEED_PASSWORD` constants before loading `src/litespeed.inc.php` in tests.

## Conventions

- All `Plugin.php` methods are `public static function` accepting `GenericEvent $event`
- Always call `$event->stopPropagation()` after handling in activate/deactivate/change_ip handlers
- Check `$event['category'] == get_service_define('LITESPEED')` before acting in every handler
- Module constant: `self::$module = 'licenses'`
- Product codes: `LSWS` (Web Server), `LSLB` (Load Balancer); tiers: 1/2/4/8-CPU, VPS, Ultra-VPS (`WS_F`)
- Tab indentation (per `.scrutinizer.yml`), camelCase params and properties
- Commit messages: lowercase descriptive (`fix litespeed activation`, `add ip change logging`)

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

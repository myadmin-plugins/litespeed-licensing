---
name: license-provisioning
description: Adds or modifies license activation/deactivation functions in `src/litespeed.inc.php` using `\Ganesh\LiteSpeed\LiteSpeedClient`. Covers duplicate-check guard, credit-balance guard, `request_log()` + `myadmin_log()` calls, and admin error email via `\MyAdmin\Mail`. Use when user says 'add activation', 'new provision function', 'deactivate by serial', 'change_ip handler', or modifies LiteSpeed API calls. Do NOT use for Plugin.php hook registration or UI/list page changes.
---
# License Provisioning

## Critical

- **Never** use the legacy `\Detain\LiteSpeed\LiteSpeed` client for new functions — always use `\Ganesh\LiteSpeed\LiteSpeedClient(LITESPEED_USERNAME, LITESPEED_PASSWORD, true)`.
- Every API call **must** be followed immediately by both `request_log()` and `myadmin_log()` — never log only one.
- Activation functions **must** run the duplicate-IP guard (`getLicenseDetails`) and credit-balance guard (`getBalance`) before calling `order()`, unless `$lic_check = false`.
- Credit balance guard **must** skip the check when `$product == 'WS_F'`.
- On any API failure, send an admin error email via `(new \MyAdmin\Mail())->adminMail(...)` — never silently swallow errors.
- After adding a new function to `src/litespeed.inc.php`, register it in `src/Plugin.php::getRequirements()` with `$loader->add_requirement('function_name', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php')`.

## Instructions

### Step 1 — Add the activation function to `src/litespeed.inc.php`

All provisioning functions are procedural and live in `src/litespeed.inc.php`. The canonical signature for activation:

```php
/**
 * Order new license
 *
 * @param string  $ipAddress
 * @param string  $product      e.g. 'LSWS', 'LSLB', 'WS_F'
 * @param string  $period       'monthly' | 'yearly' | 'owned'
 * @param string  $paymentType  'credit' | 'creditcard'
 * @param int|false $cvv
 * @param bool    $lic_check    true = new activation, false = reactivation (skips duplicate guard)
 *
 * @return array $response
 */
function activate_litespeed_new($ipAddress, $product, $period = 'monthly', $paymentType = 'credit', $cvv = false, $lic_check = true)
{
    $continue = true;
    $litespeed = new \Ganesh\LiteSpeed\LiteSpeedClient(LITESPEED_USERNAME, LITESPEED_PASSWORD, true);

    // Guard 1: duplicate IP check
    $licenseCheck = $litespeed->getLicenseDetails('IP', $ipAddress);
    if ($lic_check && isset($licenseCheck['LiteSpeed_eService']['result']) && $licenseCheck['LiteSpeed_eService']['result'] == 'success' && $licenseCheck['LiteSpeed_eService']['message'] && preg_match("/found/i", $licenseCheck['LiteSpeed_eService']['message'])) {
        $continue = false;
        request_log('licenses', false, __FUNCTION__, 'litespeed', 'getLicenseDetails', [$ipAddress], $licenseCheck);
        myadmin_log('licenses', 'info', "LicenseCheck ({$ipAddress}) Response: ".json_encode($licenseCheck), __LINE__, __FILE__);
        $subject = "LiteSpeed Order Failed ipAddress {$ipAddress} already present {$licenseCheck['LiteSpeed_eService']['credit']}";
        $body = $subject.'<br>'.nl2br(json_encode($licenseCheck, JSON_PRETTY_PRINT));
        (new \MyAdmin\Mail())->adminMail($subject, $body, false, 'admin/licenses_error.tpl');
    }

    // Guard 2: credit balance check (skip for WS_F product)
    $creditBalanceCheck = $litespeed->getBalance();
    if (isset($creditBalanceCheck['LiteSpeed_eService']['result']) && $creditBalanceCheck['LiteSpeed_eService']['result'] == 'success' && $product != 'WS_F' && $creditBalanceCheck['LiteSpeed_eService']['credit'] && floatval($creditBalanceCheck['LiteSpeed_eService']['credit']) <= 5.00) {
        $continue = false;
        request_log('licenses', false, __FUNCTION__, 'litespeed', 'getBalance', [$ipAddress], $creditBalanceCheck);
        myadmin_log('licenses', 'info', "creditBalanceCheck Response: ".json_encode($creditBalanceCheck), __LINE__, __FILE__);
        $subject = "LiteSpeed Order Failed Credit balance is low {$creditBalanceCheck['LiteSpeed_eService']['credit']}";
        $body = $subject.'<br>Order Failed for IP : '.$ipAddress.' '.nl2br(json_encode($creditBalanceCheck, JSON_PRETTY_PRINT));
        (new \MyAdmin\Mail())->adminMail($subject, $body, false, 'admin/licenses_error.tpl');
        chatNotify('LiteSpeed Order failed for IP '.$ipAddress.'. Balance is low ('.$creditBalanceCheck['LiteSpeed_eService']['credit'].')', 'hardware');
    }

    if ($continue) {
        $action = $lic_check == false ? 'Reactivate' : 'Activate';
        $response = $litespeed->order($product, $period, $paymentType, $cvv, $ipAddress);
        request_log('licenses', false, __FUNCTION__, 'litespeed', 'order', [$ipAddress, $product, $period, $paymentType, $cvv], $response);
        myadmin_log('licenses', 'info', "{$action} LiteSpeed ({$ipAddress}, {$product}, {$period}, {$paymentType}, {$cvv}) Response: ".json_encode($response), __LINE__, __FILE__);
        if (isset($response['LiteSpeed_eService']['serial'])) {
            myadmin_log('licenses', 'info', "Good, got LiteSpeed serial {$response['LiteSpeed_eService']['serial']}", __LINE__, __FILE__);
        } else {
            $subject = "Partial or Problematic LiteSpeed Order {$response['LiteSpeed_eService']['license_id']}";
            $body = $subject.'<br>'.nl2br(json_encode($response, JSON_PRETTY_PRINT));
            (new \MyAdmin\Mail())->adminMail($subject, $body, false, 'admin/licenses_error.tpl');
        }
    }
    return $response;
}
```

Verify: function is reachable from CLI with `vendor/bin/phpunit` before proceeding.

### Step 2 — Add the deactivation function to `src/litespeed.inc.php`

Deactivation takes a serial (not IP). On API error, build a `TFSmarty` email body — **do not** use the simple `adminMail` string path used in activation errors:

```php
/**
 * Cancel license by serial
 *
 * @param string $licenseSerial
 * @return array $response
 */
function deactivate_litespeed_new($licenseSerial)
{
    $litespeed = new \Ganesh\LiteSpeed\LiteSpeedClient(LITESPEED_USERNAME, LITESPEED_PASSWORD, true);
    $response = $litespeed->cancel($licenseSerial);
    if ($response['LiteSpeed_eService']['result'] == 'error') {
        $bodyRows = [];
        $bodyRows[] = 'License Serial: '.$licenseSerial.' unable to deactivate.';
        $bodyRows[] = 'Deactivation Response: .'.json_encode($response);
        $subject = 'LiteSpeed License Deactivation Issue Serial: '.$licenseSerial;
        $smartyE = new TFSmarty();
        $smartyE->assign('h1', 'LiteSpeed License Deactivation');
        $smartyE->assign('body_rows', $bodyRows);
        $msg = $smartyE->fetch('email/client/client_email.tpl');
        (new \MyAdmin\Mail())->adminMail($subject, $msg, false, 'client/client_email.tpl');
    }
    request_log('licenses', false, __FUNCTION__, 'litespeed', 'cancel', [false, $licenseSerial], $response);
    myadmin_log('licenses', 'info', "Deactivate LiteSpeed ({$licenseSerial}) Resposne: ".json_encode($response), __LINE__, __FILE__);
    return $response;
}
```

Note: keep the typo `Resposne` in the log message — it matches the existing codebase and changing it would break log parsers.

### Step 3 — Register new functions in `src/Plugin.php::getRequirements()`

For every new function added to `src/litespeed.inc.php`, add a line in `src/Plugin.php` inside `getRequirements()`:

```php
$loader->add_requirement('your_new_function_name', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php');
```

Existing registrations to model from (`src/Plugin.php:145-148`):
```php
$loader->add_requirement('deactivate_litespeed', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php');
$loader->add_requirement('activate_litespeed', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php');
$loader->add_requirement('activate_litespeed_new', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php');
$loader->add_requirement('deactivate_litespeed_new', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php');
```

Verify: `grep 'your_new_function_name' src/Plugin.php` returns a match.

### Step 4 — Wire the function in `src/Plugin.php` hook handler

Call `function_requirements('your_function')` before invoking it. Activation handlers set serial via chained setters; on missing serial set status to `pending`:

```php
function_requirements('activate_litespeed_new');
$response = activate_litespeed_new($serviceClass->getIp(), $event['field1'], 'monthly', 'credit', false, true);
if (isset($response['LiteSpeed_eService']['serial'])) {
    $serviceClass
        ->setKey($response['LiteSpeed_eService']['serial'])
        ->setExtra($response['LiteSpeed_eService']['serial'])
        ->save();
} else {
    $serviceClass->setStatus('pending')->save();
    myadmin_log(self::$module, 'info', 'LiteSpeed License '.$serviceClass->getId().' - Status changed to pending.', __LINE__, __FILE__, self::$module, $serviceClass->getId());
    $event['success'] = false;
}
$event->stopPropagation();
```

Always call `$event->stopPropagation()` at the end of a handler block.

### Step 5 — Run tests

```bash
vendor/bin/phpunit
```

## Examples

**User says:** "Add a reactivate function that skips the duplicate IP check"

**Actions taken:**
1. In `src/litespeed.inc.php`, call `activate_litespeed_new($ip, $product, 'monthly', 'credit', false, false)` — the final `false` sets `$lic_check = false`, bypassing the `getLicenseDetails` guard while still running the credit balance guard.
2. In `src/Plugin.php::getActivate()`, detect `$event['activation_type'] == 'reactivate'` and pass `false` as the last arg:
   ```php
   $response = activate_litespeed_new(
       $serviceClass->getIp(),
       $event['field1'],
       'monthly',
       'credit',
       false,
       isset($event['activation_type']) && $event['activation_type'] == 'reactivate' ? false : true
   );
   ```
3. No new function registration needed — `activate_litespeed_new` is already registered.

**Result:** Reactivation uses the same function with duplicate check disabled; credit guard still protects against low-balance orders.

## Common Issues

**`Call to undefined function activate_litespeed_new()`**
— Missing `function_requirements('activate_litespeed_new')` call before invoking it in the hook handler. Add it immediately before the call in `src/Plugin.php`.

**`Undefined constant LITESPEED_USERNAME`**
— The constant is only defined after `licenses.settings` hook fires. In CLI test scripts (`bin/`), manually define it or load the settings bootstrap before instantiating `LiteSpeedClient`.

**Credit guard fires for `WS_F` licenses unexpectedly**
— Check that `$product` exactly equals `'WS_F'` (string, no extra whitespace). The guard condition is `$product != 'WS_F'` — any variation will trigger the low-balance abort.

**`$response` undefined when `$continue = false`**
— Both guards set `$continue = false` and return early from the `if ($continue)` block, leaving `$response` unset. If your calling code checks `$response['LiteSpeed_eService']['serial']`, wrap the return in a null-safe check or initialize `$response = []` before the guards.

**Deactivation email sends raw JSON instead of formatted HTML**
— Deactivation errors use `TFSmarty` + `email/client/client_email.tpl`, not a plain string body. If you see raw JSON in the email, you used the activation error pattern (`adminMail($subject, $body, ...)`) instead of fetching the Smarty template first.

**Tests fail with `Class 'TFSmarty' not found`**
— `TFSmarty` is a MyAdmin global class, not available in isolated unit tests. Mock it or skip deactivation email tests in `tests/` unless the full MyAdmin bootstrap is loaded.

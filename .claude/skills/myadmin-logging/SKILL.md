---
name: myadmin-logging
description: Applies the dual-logging pattern (request_log + myadmin_log) after every LiteSpeed API call. Use whenever adding or reviewing code that calls LiteSpeedClient or LiteSpeed methods (order, cancel, getLicenseDetails, getBalance). Trigger phrases: 'add logging', 'log the response', 'why no log', 'missing log'. Do NOT use for non-API code paths or generic PHP logging outside the licenses module.
---
# myadmin-logging

## Critical

- **Both calls are required after every API method call** — `request_log` AND `myadmin_log`. Never use one without the other.
- The module argument is always `'licenses'` in this plugin. Never use a different module string.
- `request_log` must come **immediately after** the API call, before any conditional branching on the response.
- Exception: in `deactivate_litespeed_new` and `deactivate_litespeed`, the `request_log`/`myadmin_log` pair appears **after** the error-email block — this is the established pattern for deactivation functions. For activation functions, log immediately after the call.
- Never log credentials (`LITESPEED_USERNAME`, `LITESPEED_PASSWORD`, CVV) in the `myadmin_log` message body.

## Instructions

1. **Identify the API method being called.** Every call to `\Ganesh\LiteSpeed\LiteSpeedClient` or `\Detain\LiteSpeed\LiteSpeed` must be followed by the dual-log block. The method name string in `request_log` must exactly match the PHP method called (e.g., `'order'`, `'cancel'`, `'getLicenseDetails'`, `'getBalance'`).

   Verify: the variable holding the response (`$response`, `$licenseCheck`, `$creditBalanceCheck`, etc.) is assigned before the log calls.

2. **Add `request_log` immediately after the API call** (before any `if` on the response, except for deactivation functions — see Critical above):

   ```php
   request_log('licenses', false, __FUNCTION__, 'litespeed', 'methodName', [$arg1, $arg2], $response);
   ```

   Argument positions:
   - `'licenses'` — module, always literal
   - `false` — second param, always `false` here
   - `__FUNCTION__` — calling function name, always use the magic constant
   - `'litespeed'` — provider slug, always literal
   - `'methodName'` — the exact API method called as a string
   - `[$arg1, $arg2]` — array of arguments passed to the API method (same order as the call)
   - `$response` — the return value of the API call

   Verify: `$arg` array matches the actual arguments in the API call above it.

3. **Add `myadmin_log` on the next line**, encoding the full response as JSON:

   ```php
   myadmin_log('licenses', 'info', "ActionName ({$contextVar}) Response: ".json_encode($response), __LINE__, __FILE__);
   ```

   Message format: `"<ActionName> (<contextual identifier>) Response: ".json_encode($response)`
   - `<ActionName>` — brief description matching the function purpose (e.g., `"Activate LiteSpeed"`, `"LicenseCheck"`, `"creditBalanceCheck"`, `"Deactivate LiteSpeed"`)
   - `<contextual identifier>` — the primary identifier for this call, typically `{$ipAddress}` for activations or `{$licenseSerial}` for deactivations
   - Always `__LINE__, __FILE__` — never hardcode line numbers

   Verify: `__LINE__` and `__FILE__` are present as the 4th and 5th arguments.

4. **For guard/check calls** (e.g., `getLicenseDetails`, `getBalance`), add the log pair inside the `if` block where the check fails, not outside — because these calls only matter when the guard trips:

   ```php
   $licenseCheck = $litespeed->getLicenseDetails('IP', $ipAddress);
   if ($lic_check && isset($licenseCheck['LiteSpeed_eService']['result']) && ...) {
       $continue = false;
       request_log('licenses', false, __FUNCTION__, 'litespeed', 'getLicenseDetails', [$ipAddress], $licenseCheck);
       myadmin_log('licenses', 'info', "LicenseCheck ({$ipAddress}) Response: ".json_encode($licenseCheck), __LINE__, __FILE__);
       // ... admin mail
   }
   ```

   Verify: the log is inside the guard `if`, not after it.

5. **For success sub-conditions**, add a second `myadmin_log` confirming the success value extracted from the response:

   ```php
   if (isset($response['LiteSpeed_eService']['serial'])) {
       myadmin_log('licenses', 'info', "Good, got LiteSpeed serial {$response['LiteSpeed_eService']['serial']}", __LINE__, __FILE__);
   }
   ```

   This second `myadmin_log` is in addition to, not a replacement for, the response log in Step 3.

## Examples

**User says:** "Add logging to this new `reactivate_litespeed_v2` function that calls `$litespeed->order()`"

**Before:**
```php
function reactivate_litespeed_v2($ipAddress, $product) {
    $litespeed = new \Ganesh\LiteSpeed\LiteSpeedClient(LITESPEED_USERNAME, LITESPEED_PASSWORD, true);
    $response = $litespeed->order($product, 'monthly', 'credit', false, $ipAddress);
    if (isset($response['LiteSpeed_eService']['serial'])) {
        // handle success
    }
    return $response;
}
```

**After:**
```php
function reactivate_litespeed_v2($ipAddress, $product) {
    $litespeed = new \Ganesh\LiteSpeed\LiteSpeedClient(LITESPEED_USERNAME, LITESPEED_PASSWORD, true);
    $response = $litespeed->order($product, 'monthly', 'credit', false, $ipAddress);
    request_log('licenses', false, __FUNCTION__, 'litespeed', 'order', [$ipAddress, $product, 'monthly', 'credit', false], $response);
    myadmin_log('licenses', 'info', "Reactivate LiteSpeed ({$ipAddress}, {$product}) Response: ".json_encode($response), __LINE__, __FILE__);
    if (isset($response['LiteSpeed_eService']['serial'])) {
        myadmin_log('licenses', 'info', "Good, got LiteSpeed serial {$response['LiteSpeed_eService']['serial']}", __LINE__, __FILE__);
    }
    return $response;
}
```

**Result:** Both `request_log` and `myadmin_log` appear immediately after the `order()` call, before the `if` branch. A second `myadmin_log` confirms serial on success. The `$args` array in `request_log` matches exactly what was passed to `order()`.

## Common Issues

**Missing `request_log` / only `myadmin_log` present:**
Both are required. `request_log` feeds structured request/response data to the request log table. `myadmin_log` feeds human-readable text to the activity log. Add the missing call — they are never interchangeable.

**Wrong method name string in `request_log`:**
If the call is `$litespeed->cancel($serial)` but you wrote `'deactivate'`, the log is misleading. The string must match the PHP method: `'cancel'`, `'order'`, `'getLicenseDetails'`, `'getBalance'`.

**`$args` array in `request_log` does not match the API call:**
If the call is `$litespeed->order($product, $period, $paymentType, $cvv, $ipAddress)` the args array must be `[$ipAddress, $product, $period, $paymentType, $cvv]` (or in the exact call order). Copy-paste from the actual call site — do not guess.

**`myadmin_log` missing `__LINE__` / `__FILE__`:**
Signature is `myadmin_log($module, $level, $message, __LINE__, __FILE__)`. Omitting the last two arguments will either cause a PHP error or silently drop file/line info from log records.

**Logging inside a `foreach` with no guard:**
If an API call is in a loop, every iteration logs. This is expected, but confirm it is intentional before adding the log pair inside a loop body.

**`json_encode($response)` returns `false`:**
If the response contains non-UTF-8 bytes, `json_encode` returns `false` and logs `"Response: "`. Use `json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR)` when response data comes from an external XML/SOAP source that may have encoding issues.
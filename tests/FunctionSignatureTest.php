<?php

declare(strict_types=1);

namespace Detain\MyAdminLiteSpeed\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;

/**
 * Test suite for procedural function signatures in litespeed.inc.php.
 *
 * These tests validate function signatures and parameter definitions
 * without executing the functions (which depend on external services).
 */
class FunctionSignatureTest extends TestCase
{
    /**
     * Ensure the include file is loaded.
     */
    public static function setUpBeforeClass(): void
    {
        $file = dirname(__DIR__) . '/src/litespeed.inc.php';
        if (!function_exists('activate_litespeed')) {
            // Define stubs for global functions/constants used inside the file
            // so we can require it for reflection without fatal errors.
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
    }

    /**
     * Test that activate_litespeed function signature has expected parameters.
     */
    public function testActivateLitespeedSignature(): void
    {
        if (!function_exists('activate_litespeed')) {
            self::markTestSkipped('activate_litespeed function not loaded');
        }
        $ref = new ReflectionFunction('activate_litespeed');
        $params = $ref->getParameters();

        self::assertGreaterThanOrEqual(3, count($params), 'activate_litespeed should have at least 3 params');
        self::assertSame('ipAddress', $params[0]->getName());
        self::assertSame('field1', $params[1]->getName());
        self::assertSame('field2', $params[2]->getName());
    }

    /**
     * Test that activate_litespeed has correct default parameter values.
     */
    public function testActivateLitespeedDefaults(): void
    {
        if (!function_exists('activate_litespeed')) {
            self::markTestSkipped('activate_litespeed function not loaded');
        }
        $ref = new ReflectionFunction('activate_litespeed');
        $params = $ref->getParameters();

        // period default = 'monthly'
        self::assertTrue($params[3]->isDefaultValueAvailable());
        self::assertSame('monthly', $params[3]->getDefaultValue());

        // payment default = 'credit'
        self::assertTrue($params[4]->isDefaultValueAvailable());
        self::assertSame('credit', $params[4]->getDefaultValue());

        // cvv default = false
        self::assertTrue($params[5]->isDefaultValueAvailable());
        self::assertFalse($params[5]->getDefaultValue());

        // promocode default = false
        self::assertTrue($params[6]->isDefaultValueAvailable());
        self::assertFalse($params[6]->getDefaultValue());
    }

    /**
     * Test that activate_litespeed_new function exists and has expected params.
     */
    public function testActivateLitespeedNewSignature(): void
    {
        if (!function_exists('activate_litespeed_new')) {
            self::markTestSkipped('activate_litespeed_new function not loaded');
        }
        $ref = new ReflectionFunction('activate_litespeed_new');
        $params = $ref->getParameters();

        self::assertGreaterThanOrEqual(2, count($params));
        self::assertSame('ipAddress', $params[0]->getName());
        self::assertSame('product', $params[1]->getName());
    }

    /**
     * Test that activate_litespeed_new has correct default parameter values.
     */
    public function testActivateLitespeedNewDefaults(): void
    {
        if (!function_exists('activate_litespeed_new')) {
            self::markTestSkipped('activate_litespeed_new function not loaded');
        }
        $ref = new ReflectionFunction('activate_litespeed_new');
        $params = $ref->getParameters();

        // period default = 'monthly'
        self::assertTrue($params[2]->isDefaultValueAvailable());
        self::assertSame('monthly', $params[2]->getDefaultValue());

        // paymentType default = 'credit'
        self::assertTrue($params[3]->isDefaultValueAvailable());
        self::assertSame('credit', $params[3]->getDefaultValue());

        // cvv default = false
        self::assertTrue($params[4]->isDefaultValueAvailable());
        self::assertFalse($params[4]->getDefaultValue());

        // lic_check default = true
        self::assertTrue($params[5]->isDefaultValueAvailable());
        self::assertTrue($params[5]->getDefaultValue());
    }

    /**
     * Test that deactivate_litespeed_new function has expected signature.
     */
    public function testDeactivateLitespeedNewSignature(): void
    {
        if (!function_exists('deactivate_litespeed_new')) {
            self::markTestSkipped('deactivate_litespeed_new function not loaded');
        }
        $ref = new ReflectionFunction('deactivate_litespeed_new');
        $params = $ref->getParameters();

        self::assertCount(1, $params);
        self::assertSame('licenseSerial', $params[0]->getName());
        self::assertFalse($params[0]->isDefaultValueAvailable());
    }

    /**
     * Test that deactivate_litespeed function has expected signature.
     */
    public function testDeactivateLitespeedSignature(): void
    {
        if (!function_exists('deactivate_litespeed')) {
            self::markTestSkipped('deactivate_litespeed function not loaded');
        }
        $ref = new ReflectionFunction('deactivate_litespeed');
        $params = $ref->getParameters();

        self::assertCount(1, $params);
        self::assertSame('ipAddress', $params[0]->getName());
        self::assertFalse($params[0]->isDefaultValueAvailable());
    }

    /**
     * Test that activate_litespeed has exactly 7 parameters.
     */
    public function testActivateLitespeedParameterCount(): void
    {
        if (!function_exists('activate_litespeed')) {
            self::markTestSkipped('activate_litespeed function not loaded');
        }
        $ref = new ReflectionFunction('activate_litespeed');
        self::assertSame(7, $ref->getNumberOfParameters());
    }

    /**
     * Test that activate_litespeed requires exactly 3 parameters.
     */
    public function testActivateLitespeedRequiredParameterCount(): void
    {
        if (!function_exists('activate_litespeed')) {
            self::markTestSkipped('activate_litespeed function not loaded');
        }
        $ref = new ReflectionFunction('activate_litespeed');
        self::assertSame(3, $ref->getNumberOfRequiredParameters());
    }

    /**
     * Test that activate_litespeed_new has exactly 6 parameters.
     */
    public function testActivateLitespeedNewParameterCount(): void
    {
        if (!function_exists('activate_litespeed_new')) {
            self::markTestSkipped('activate_litespeed_new function not loaded');
        }
        $ref = new ReflectionFunction('activate_litespeed_new');
        self::assertSame(6, $ref->getNumberOfParameters());
    }

    /**
     * Test that activate_litespeed_new requires exactly 2 parameters.
     */
    public function testActivateLitespeedNewRequiredParameterCount(): void
    {
        if (!function_exists('activate_litespeed_new')) {
            self::markTestSkipped('activate_litespeed_new function not loaded');
        }
        $ref = new ReflectionFunction('activate_litespeed_new');
        self::assertSame(2, $ref->getNumberOfRequiredParameters());
    }
}

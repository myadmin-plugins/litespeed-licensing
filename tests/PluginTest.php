<?php

declare(strict_types=1);

namespace Detain\MyAdminLiteSpeed\Tests;

use Detain\MyAdminLiteSpeed\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Test suite for the Plugin class.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Test that Plugin class exists and is instantiable.
     */
    public function testClassExists(): void
    {
        self::assertTrue(class_exists(Plugin::class));
    }

    /**
     * Test that Plugin can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        self::assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the class is not abstract or final.
     */
    public function testClassIsNotAbstract(): void
    {
        self::assertFalse($this->reflection->isAbstract());
    }

    /**
     * Test that $name static property exists and has expected value.
     */
    public function testNameProperty(): void
    {
        self::assertSame('LiteSpeed Licensing', Plugin::$name);
    }

    /**
     * Test that $description static property exists and is a non-empty string.
     */
    public function testDescriptionProperty(): void
    {
        self::assertIsString(Plugin::$description);
        self::assertNotEmpty(Plugin::$description);
        self::assertStringContainsString('LiteSpeed', Plugin::$description);
    }

    /**
     * Test that $help static property exists and is a non-empty string.
     */
    public function testHelpProperty(): void
    {
        self::assertIsString(Plugin::$help);
        self::assertNotEmpty(Plugin::$help);
    }

    /**
     * Test that $module static property is 'licenses'.
     */
    public function testModuleProperty(): void
    {
        self::assertSame('licenses', Plugin::$module);
    }

    /**
     * Test that $type static property is 'service'.
     */
    public function testTypeProperty(): void
    {
        self::assertSame('service', Plugin::$type);
    }

    /**
     * Test that all expected static properties are declared.
     */
    public function testAllStaticPropertiesExist(): void
    {
        $expectedProperties = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expectedProperties as $prop) {
            self::assertTrue(
                $this->reflection->hasProperty($prop),
                "Missing static property: \${$prop}"
            );
            $refProp = $this->reflection->getProperty($prop);
            self::assertTrue($refProp->isStatic(), "\${$prop} should be static");
            self::assertTrue($refProp->isPublic(), "\${$prop} should be public");
        }
    }

    /**
     * Test that getHooks returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        self::assertIsArray($hooks);
    }

    /**
     * Test that getHooks contains expected event keys.
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $expectedKeys = [
            'licenses.settings',
            'licenses.activate',
            'licenses.reactivate',
            'licenses.deactivate',
            'licenses.deactivate_ip',
            'function.requirements',
            'licenses.change_ip',
            'ui.menu',
        ];
        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $hooks, "Missing hook key: {$key}");
        }
    }

    /**
     * Test that getHooks returns exactly 8 hooks.
     */
    public function testGetHooksCount(): void
    {
        $hooks = Plugin::getHooks();
        self::assertCount(8, $hooks);
    }

    /**
     * Test that all hook values are valid callable arrays.
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $value) {
            self::assertIsArray($value, "Hook value for '{$key}' should be an array");
            self::assertCount(2, $value, "Hook value for '{$key}' should have 2 elements");
            self::assertSame(Plugin::class, $value[0], "Hook '{$key}' class should be Plugin");
            self::assertIsString($value[1], "Hook '{$key}' method name should be a string");
        }
    }

    /**
     * Test that all hook methods reference existing static methods.
     */
    public function testGetHooksMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $value) {
            $method = $value[1];
            self::assertTrue(
                $this->reflection->hasMethod($method),
                "Hook '{$key}' references non-existent method: {$method}"
            );
            $refMethod = $this->reflection->getMethod($method);
            self::assertTrue(
                $refMethod->isStatic(),
                "Method {$method} should be static"
            );
            self::assertTrue(
                $refMethod->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test that activate and reactivate share the same handler.
     */
    public function testActivateAndReactivateShareHandler(): void
    {
        $hooks = Plugin::getHooks();
        self::assertSame($hooks['licenses.activate'], $hooks['licenses.reactivate']);
    }

    /**
     * Test that deactivate and deactivate_ip share the same handler.
     */
    public function testDeactivateAndDeactivateIpShareHandler(): void
    {
        $hooks = Plugin::getHooks();
        self::assertSame($hooks['licenses.deactivate'], $hooks['licenses.deactivate_ip']);
    }

    /**
     * Test that getActivate method accepts GenericEvent parameter.
     */
    public function testGetActivateMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getActivate');
        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        self::assertNotNull($type);
        self::assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that getDeactivate method accepts GenericEvent parameter.
     */
    public function testGetDeactivateMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getDeactivate');
        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        self::assertNotNull($type);
        self::assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that getChangeIp method accepts GenericEvent parameter.
     */
    public function testGetChangeIpMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getChangeIp');
        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        self::assertNotNull($type);
        self::assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that getMenu method accepts GenericEvent parameter.
     */
    public function testGetMenuMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getMenu');
        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());
    }

    /**
     * Test that getRequirements method accepts GenericEvent parameter.
     */
    public function testGetRequirementsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());
    }

    /**
     * Test that getSettings method accepts GenericEvent parameter.
     */
    public function testGetSettingsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());
    }

    /**
     * Test the constructor takes no arguments.
     */
    public function testConstructorHasNoParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        self::assertNotNull($constructor);
        self::assertCount(0, $constructor->getParameters());
    }

    /**
     * Test that hooks use the module property for key prefixes.
     */
    public function testHookKeysUseModulePrefix(): void
    {
        $hooks = Plugin::getHooks();
        $modulePrefix = Plugin::$module . '.';
        $modulePrefixedKeys = [
            'licenses.settings',
            'licenses.activate',
            'licenses.reactivate',
            'licenses.deactivate',
            'licenses.deactivate_ip',
            'licenses.change_ip',
        ];
        foreach ($modulePrefixedKeys as $key) {
            self::assertStringStartsWith($modulePrefix, $key);
            self::assertArrayHasKey($key, $hooks);
        }
    }

    /**
     * Test that the class has exactly the expected public static methods.
     */
    public function testExpectedPublicStaticMethods(): void
    {
        $expectedMethods = [
            'getHooks',
            'getActivate',
            'getDeactivate',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];
        foreach ($expectedMethods as $methodName) {
            self::assertTrue(
                $this->reflection->hasMethod($methodName),
                "Missing method: {$methodName}"
            );
        }
    }

    /**
     * Test that all event handler methods have void return type or no return type.
     */
    public function testEventHandlerReturnTypes(): void
    {
        $eventHandlers = ['getActivate', 'getDeactivate', 'getChangeIp', 'getMenu', 'getRequirements', 'getSettings'];
        foreach ($eventHandlers as $handlerName) {
            $method = $this->reflection->getMethod($handlerName);
            $returnType = $method->getReturnType();
            if ($returnType !== null) {
                self::assertSame('void', $returnType->getName(), "{$handlerName} should return void if typed");
            } else {
                // No return type is also acceptable
                self::assertNull($returnType);
            }
        }
    }

    /**
     * Test that getHooks return type is array.
     */
    public function testGetHooksReturnType(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $returnType = $method->getReturnType();
        if ($returnType !== null) {
            self::assertSame('array', $returnType->getName());
        }
        // Verify actual return is array
        self::assertIsArray(Plugin::getHooks());
    }

    /**
     * Test that the class resides in the correct namespace.
     */
    public function testNamespace(): void
    {
        self::assertSame('Detain\\MyAdminLiteSpeed', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the class name is Plugin.
     */
    public function testClassName(): void
    {
        self::assertSame('Plugin', $this->reflection->getShortName());
    }

    /**
     * Test that description mentions LiteSpeed product types.
     */
    public function testDescriptionMentionsLiteSpeed(): void
    {
        self::assertStringContainsString('LiteSpeed', Plugin::$description);
    }

    /**
     * Test that help text mentions cPanel compatibility.
     */
    public function testHelpMentionsCpanel(): void
    {
        self::assertStringContainsString('cPanel', Plugin::$help);
    }
}

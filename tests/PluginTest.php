<?php

declare(strict_types=1);

namespace Detain\MyAdminLicenses\Tests;

use Detain\MyAdminLicenses\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Test suite for the Plugin class.
 *
 * Validates class structure, static properties, hook registration,
 * settings configuration, and event handler method signatures.
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
     * Test that the Plugin class can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the constructor requires no arguments.
     */
    public function testConstructorHasNoRequiredParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Test that the $name static property is set to 'Licensing'.
     */
    public function testNamePropertyValue(): void
    {
        $this->assertSame('Licensing', Plugin::$name);
    }

    /**
     * Test that the $description static property is a non-empty string.
     */
    public function testDescriptionPropertyValue(): void
    {
        $this->assertSame('Allows selling of Licenses.', Plugin::$description);
    }

    /**
     * Test that the $module static property is 'licenses'.
     */
    public function testModulePropertyValue(): void
    {
        $this->assertSame('licenses', Plugin::$module);
    }

    /**
     * Test that the $type static property is 'module'.
     */
    public function testTypePropertyValue(): void
    {
        $this->assertSame('module', Plugin::$type);
    }

    /**
     * Test that the $help static property is an empty string.
     */
    public function testHelpPropertyIsEmptyString(): void
    {
        $this->assertSame('', Plugin::$help);
    }

    /**
     * Test that all expected static properties exist on the class.
     */
    public function testAllStaticPropertiesExist(): void
    {
        $expected = ['name', 'description', 'help', 'module', 'type', 'settings'];
        foreach ($expected as $property) {
            $this->assertTrue(
                $this->reflection->hasProperty($property),
                "Missing static property: \${$property}"
            );
            $this->assertTrue(
                $this->reflection->getProperty($property)->isStatic(),
                "Property \${$property} should be static"
            );
            $this->assertTrue(
                $this->reflection->getProperty($property)->isPublic(),
                "Property \${$property} should be public"
            );
        }
    }

    /**
     * Test that the $settings array contains all required keys.
     */
    public function testSettingsContainsRequiredKeys(): void
    {
        $requiredKeys = [
            'SERVICE_ID_OFFSET',
            'USE_REPEAT_INVOICE',
            'USE_PACKAGES',
            'BILLING_DAYS_OFFSET',
            'IMGNAME',
            'REPEAT_BILLING_METHOD',
            'DELETE_PENDING_DAYS',
            'SUSPEND_DAYS',
            'SUSPEND_WARNING_DAYS',
            'TITLE',
            'EMAIL_FROM',
            'TBLNAME',
            'TABLE',
            'PREFIX',
            'TITLE_FIELD',
            'TITLE_FIELD2',
            'MENUNAME',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, Plugin::$settings, "Missing settings key: {$key}");
        }
    }

    /**
     * Test specific settings values for correctness.
     */
    public function testSettingsValues(): void
    {
        $this->assertSame(5000, Plugin::$settings['SERVICE_ID_OFFSET']);
        $this->assertTrue(Plugin::$settings['USE_REPEAT_INVOICE']);
        $this->assertTrue(Plugin::$settings['USE_PACKAGES']);
        $this->assertSame(0, Plugin::$settings['BILLING_DAYS_OFFSET']);
        $this->assertSame('certificate.png', Plugin::$settings['IMGNAME']);
        $this->assertSame(45, Plugin::$settings['DELETE_PENDING_DAYS']);
        $this->assertSame(9, Plugin::$settings['SUSPEND_DAYS']);
        $this->assertSame(7, Plugin::$settings['SUSPEND_WARNING_DAYS']);
        $this->assertSame('Licensing', Plugin::$settings['TITLE']);
        $this->assertSame('invoice@cpaneldirect.net', Plugin::$settings['EMAIL_FROM']);
        $this->assertSame('Licenses', Plugin::$settings['TBLNAME']);
        $this->assertSame('licenses', Plugin::$settings['TABLE']);
        $this->assertSame('license', Plugin::$settings['PREFIX']);
        $this->assertSame('license_ip', Plugin::$settings['TITLE_FIELD']);
        $this->assertSame('license_hostname', Plugin::$settings['TITLE_FIELD2']);
        $this->assertSame('Licensing', Plugin::$settings['MENUNAME']);
    }

    /**
     * Test that getHooks returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks contains the expected event keys.
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $expectedKeys = [
            'api.register',
            'function.requirements',
            'licenses.load_processing',
            'licenses.settings',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $hooks, "Missing hook key: {$key}");
        }
    }

    /**
     * Test that getHooks returns exactly four hooks.
     */
    public function testGetHooksReturnsFourHooks(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(4, $hooks);
    }

    /**
     * Test that each hook value is a callable array referencing the Plugin class.
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $value) {
            $this->assertIsArray($value, "Hook '{$key}' value should be an array");
            $this->assertCount(2, $value, "Hook '{$key}' should have two elements [class, method]");
            $this->assertSame(Plugin::class, $value[0], "Hook '{$key}' first element should be Plugin class");
            $this->assertIsString($value[1], "Hook '{$key}' second element should be a method name string");
        }
    }

    /**
     * Test that all hook methods reference valid static methods on the Plugin class.
     */
    public function testGetHooksMethodsExistOnClass(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $value) {
            $method = $value[1];
            $this->assertTrue(
                $this->reflection->hasMethod($method),
                "Method '{$method}' referenced in hook '{$key}' does not exist on Plugin"
            );
            $this->assertTrue(
                $this->reflection->getMethod($method)->isStatic(),
                "Method '{$method}' should be static"
            );
            $this->assertTrue(
                $this->reflection->getMethod($method)->isPublic(),
                "Method '{$method}' should be public"
            );
        }
    }

    /**
     * Test that hook keys use the module property for module-specific hooks.
     */
    public function testGetHooksUsesModulePropertyForKeys(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey(Plugin::$module . '.load_processing', $hooks);
        $this->assertArrayHasKey(Plugin::$module . '.settings', $hooks);
    }

    /**
     * Test that apiRegister is mapped to the 'api.register' hook.
     */
    public function testApiRegisterHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'apiRegister'], $hooks['api.register']);
    }

    /**
     * Test that getRequirements is mapped to the 'function.requirements' hook.
     */
    public function testGetRequirementsHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getRequirements'], $hooks['function.requirements']);
    }

    /**
     * Test that loadProcessing is mapped to the 'licenses.load_processing' hook.
     */
    public function testLoadProcessingHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'loadProcessing'], $hooks['licenses.load_processing']);
    }

    /**
     * Test that getSettings is mapped to the 'licenses.settings' hook.
     */
    public function testGetSettingsHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getSettings'], $hooks['licenses.settings']);
    }

    /**
     * Test that the getRequirements method accepts a GenericEvent parameter.
     */
    public function testGetRequirementsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that the apiRegister method accepts a GenericEvent parameter.
     */
    public function testApiRegisterMethodSignature(): void
    {
        $method = $this->reflection->getMethod('apiRegister');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that the loadProcessing method accepts a GenericEvent parameter.
     */
    public function testLoadProcessingMethodSignature(): void
    {
        $method = $this->reflection->getMethod('loadProcessing');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that the getSettings method accepts a GenericEvent parameter.
     */
    public function testGetSettingsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that all event handler methods have void return type or no explicit return type.
     */
    public function testEventHandlerMethodsReturnVoid(): void
    {
        $methods = ['getRequirements', 'apiRegister', 'loadProcessing', 'getSettings'];
        foreach ($methods as $methodName) {
            $method = $this->reflection->getMethod($methodName);
            $returnType = $method->getReturnType();
            // These methods either have void return or no declared return type
            if ($returnType !== null) {
                $this->assertSame('void', $returnType->getName());
            } else {
                $this->assertNull($returnType);
            }
        }
    }

    /**
     * Test that getHooks has array return type.
     */
    public function testGetHooksReturnTypeIsArray(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $returnType = $method->getReturnType();
        if ($returnType !== null) {
            $this->assertSame('array', $returnType->getName());
        }
        // If no return type declared, we already tested it returns an array
        $this->assertIsArray(Plugin::getHooks());
    }

    /**
     * Test that the class is in the correct namespace.
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\\MyAdminLicenses', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the class is not abstract or final.
     */
    public function testClassIsConcreteAndNotFinal(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * Test that the class does not extend any parent class.
     */
    public function testClassHasNoParent(): void
    {
        $this->assertFalse($this->reflection->getParentClass());
    }

    /**
     * Test that the class does not implement any interfaces.
     */
    public function testClassImplementsNoInterfaces(): void
    {
        $this->assertEmpty($this->reflection->getInterfaceNames());
    }

    /**
     * Test that the Plugin class has exactly the expected public methods.
     */
    public function testClassPublicMethods(): void
    {
        $expectedMethods = [
            '__construct',
            'getHooks',
            'getRequirements',
            'apiRegister',
            'loadProcessing',
            'getSettings',
        ];

        $publicMethods = array_map(
            fn(\ReflectionMethod $m) => $m->getName(),
            $this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        foreach ($expectedMethods as $method) {
            $this->assertContains($method, $publicMethods, "Missing public method: {$method}");
        }
    }

    /**
     * Test that getRequirements calls add_requirement with expected function names
     * by using an anonymous class as the event subject.
     */
    public function testGetRequirementsAddsExpectedFunctions(): void
    {
        $requirements = [];
        $loader = new class($requirements) {
            /** @var array<int, string> */
            private array $reqs;

            /**
             * @param array<int, string> &$requirements
             */
            public function __construct(array &$requirements)
            {
                $this->reqs = &$requirements;
            }

            /**
             * @param string $name
             * @param string $path
             */
            public function add_requirement(string $name, string $path): void
            {
                $this->reqs[] = $name;
            }
        };

        $event = new GenericEvent($loader);
        Plugin::getRequirements($event);

        $expectedFunctions = [
            'api_get_license_types',
            'api_cancel_license_ip',
            'api_cancel_license',
            'api_buy_license_prepay',
            'api_buy_license',
            'api_change_license_ip',
            'api_change_license_ip_by_id',
        ];

        foreach ($expectedFunctions as $func) {
            $this->assertContains($func, $requirements, "Missing requirement: {$func}");
        }
        $this->assertCount(7, $requirements);
    }

    /**
     * Test that getRequirements passes paths pointing to the api.php file.
     */
    public function testGetRequirementsPathsPointToApiFile(): void
    {
        $paths = [];
        $loader = new class($paths) {
            /** @var array<int, string> */
            private array $paths;

            /**
             * @param array<int, string> &$paths
             */
            public function __construct(array &$paths)
            {
                $this->paths = &$paths;
            }

            /**
             * @param string $name
             * @param string $path
             */
            public function add_requirement(string $name, string $path): void
            {
                $this->paths[] = $path;
            }
        };

        $event = new GenericEvent($loader);
        Plugin::getRequirements($event);

        foreach ($paths as $path) {
            $this->assertStringEndsWith('/src/api.php', $path);
            $this->assertStringContainsString('myadmin-licenses-module', $path);
        }
    }

    /**
     * Test that the settings array contains proper data types.
     */
    public function testSettingsDataTypes(): void
    {
        $this->assertIsInt(Plugin::$settings['SERVICE_ID_OFFSET']);
        $this->assertIsBool(Plugin::$settings['USE_REPEAT_INVOICE']);
        $this->assertIsBool(Plugin::$settings['USE_PACKAGES']);
        $this->assertIsInt(Plugin::$settings['BILLING_DAYS_OFFSET']);
        $this->assertIsString(Plugin::$settings['IMGNAME']);
        $this->assertIsInt(Plugin::$settings['DELETE_PENDING_DAYS']);
        $this->assertIsInt(Plugin::$settings['SUSPEND_DAYS']);
        $this->assertIsInt(Plugin::$settings['SUSPEND_WARNING_DAYS']);
        $this->assertIsString(Plugin::$settings['TITLE']);
        $this->assertIsString(Plugin::$settings['EMAIL_FROM']);
        $this->assertIsString(Plugin::$settings['TBLNAME']);
        $this->assertIsString(Plugin::$settings['TABLE']);
        $this->assertIsString(Plugin::$settings['PREFIX']);
        $this->assertIsString(Plugin::$settings['TITLE_FIELD']);
        $this->assertIsString(Plugin::$settings['TITLE_FIELD2']);
        $this->assertIsString(Plugin::$settings['MENUNAME']);
    }
}

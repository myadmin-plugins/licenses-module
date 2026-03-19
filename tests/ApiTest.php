<?php

declare(strict_types=1);

namespace Detain\MyAdminLicenses\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;

/**
 * Test suite for the API functions defined in src/api.php.
 *
 * Validates function existence, parameter signatures, and structural
 * characteristics of the license API functions. Since these functions
 * depend on database and global state, tests focus on static analysis
 * and signature verification rather than execution.
 */
class ApiTest extends TestCase
{
    /**
     * @var list<string>
     */
    private static array $apiFunctions = [
        'api_get_license_types',
        'api_cancel_license_ip',
        'api_cancel_license',
        'api_buy_license_prepay',
        'api_buy_license',
        'api_change_license_ip',
        'api_change_license_ip_by_id',
    ];

    /**
     * Test that the api.php file exists and is readable.
     */
    public function testApiFileExists(): void
    {
        $path = dirname(__DIR__) . '/src/api.php';
        $this->assertFileExists($path);
        $this->assertFileIsReadable($path);
    }

    /**
     * Test that the api.php file starts with a PHP open tag.
     */
    public function testApiFileStartsWithPhpTag(): void
    {
        $path = dirname(__DIR__) . '/src/api.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringStartsWith('<?php', $contents);
    }

    /**
     * Test that api_get_license_types function is defined.
     */
    public function testApiGetLicenseTypesExists(): void
    {
        $this->assertTrue(function_exists('api_get_license_types'));
    }

    /**
     * Test that api_cancel_license_ip function is defined.
     */
    public function testApiCancelLicenseIpExists(): void
    {
        $this->assertTrue(function_exists('api_cancel_license_ip'));
    }

    /**
     * Test that api_cancel_license function is defined.
     */
    public function testApiCancelLicenseExists(): void
    {
        $this->assertTrue(function_exists('api_cancel_license'));
    }

    /**
     * Test that api_buy_license_prepay function is defined.
     */
    public function testApiBuyLicensePrepayExists(): void
    {
        $this->assertTrue(function_exists('api_buy_license_prepay'));
    }

    /**
     * Test that api_buy_license function is defined.
     */
    public function testApiBuyLicenseExists(): void
    {
        $this->assertTrue(function_exists('api_buy_license'));
    }

    /**
     * Test that api_change_license_ip function is defined.
     */
    public function testApiChangeLicenseIpExists(): void
    {
        $this->assertTrue(function_exists('api_change_license_ip'));
    }

    /**
     * Test that api_change_license_ip_by_id function is defined.
     */
    public function testApiChangeLicenseIpByIdExists(): void
    {
        $this->assertTrue(function_exists('api_change_license_ip_by_id'));
    }

    /**
     * Test that api_get_license_types requires no parameters.
     */
    public function testApiGetLicenseTypesHasNoParameters(): void
    {
        $ref = new ReflectionFunction('api_get_license_types');
        $this->assertCount(0, $ref->getParameters());
    }

    /**
     * Test that api_cancel_license_ip requires exactly three parameters.
     */
    public function testApiCancelLicenseIpParameterCount(): void
    {
        $ref = new ReflectionFunction('api_cancel_license_ip');
        $this->assertCount(3, $ref->getParameters());
    }

    /**
     * Test that api_cancel_license_ip has the correct parameter names.
     */
    public function testApiCancelLicenseIpParameterNames(): void
    {
        $ref = new ReflectionFunction('api_cancel_license_ip');
        $params = $ref->getParameters();
        $this->assertSame('sid', $params[0]->getName());
        $this->assertSame('ip', $params[1]->getName());
        $this->assertSame('service_type', $params[2]->getName());
    }

    /**
     * Test that api_cancel_license_ip has no optional parameters.
     */
    public function testApiCancelLicenseIpAllParamsRequired(): void
    {
        $ref = new ReflectionFunction('api_cancel_license_ip');
        foreach ($ref->getParameters() as $param) {
            $this->assertFalse($param->isOptional(), "Parameter \${$param->getName()} should be required");
        }
    }

    /**
     * Test that api_cancel_license requires exactly two parameters.
     */
    public function testApiCancelLicenseParameterCount(): void
    {
        $ref = new ReflectionFunction('api_cancel_license');
        $this->assertCount(2, $ref->getParameters());
    }

    /**
     * Test that api_cancel_license has the correct parameter names.
     */
    public function testApiCancelLicenseParameterNames(): void
    {
        $ref = new ReflectionFunction('api_cancel_license');
        $params = $ref->getParameters();
        $this->assertSame('sid', $params[0]->getName());
        $this->assertSame('id', $params[1]->getName());
    }

    /**
     * Test that api_buy_license has the correct parameter count.
     */
    public function testApiBuyLicenseParameterCount(): void
    {
        $ref = new ReflectionFunction('api_buy_license');
        $this->assertCount(5, $ref->getParameters());
    }

    /**
     * Test that api_buy_license has the correct parameter names.
     */
    public function testApiBuyLicenseParameterNames(): void
    {
        $ref = new ReflectionFunction('api_buy_license');
        $params = $ref->getParameters();
        $this->assertSame('sid', $params[0]->getName());
        $this->assertSame('ip', $params[1]->getName());
        $this->assertSame('service_type', $params[2]->getName());
        $this->assertSame('coupon', $params[3]->getName());
        $this->assertSame('use_prepay', $params[4]->getName());
    }

    /**
     * Test that api_buy_license has two optional parameters.
     */
    public function testApiBuyLicenseOptionalParameters(): void
    {
        $ref = new ReflectionFunction('api_buy_license');
        $params = $ref->getParameters();

        $this->assertFalse($params[0]->isOptional(), '$sid should be required');
        $this->assertFalse($params[1]->isOptional(), '$ip should be required');
        $this->assertFalse($params[2]->isOptional(), '$service_type should be required');
        $this->assertTrue($params[3]->isOptional(), '$coupon should be optional');
        $this->assertTrue($params[4]->isOptional(), '$use_prepay should be optional');
    }

    /**
     * Test that api_buy_license coupon parameter defaults to empty string.
     */
    public function testApiBuyLicenseCouponDefaultValue(): void
    {
        $ref = new ReflectionFunction('api_buy_license');
        $params = $ref->getParameters();
        $this->assertSame('', $params[3]->getDefaultValue());
    }

    /**
     * Test that api_buy_license use_prepay parameter defaults to null.
     */
    public function testApiBuyLicenseUsePrepayDefaultValue(): void
    {
        $ref = new ReflectionFunction('api_buy_license');
        $params = $ref->getParameters();
        $this->assertNull($params[4]->getDefaultValue());
    }

    /**
     * Test that api_buy_license_prepay has the same signature as api_buy_license.
     */
    public function testApiBuyLicensePrepayMatchesBuyLicenseSignature(): void
    {
        $refPrepay = new ReflectionFunction('api_buy_license_prepay');
        $refBuy = new ReflectionFunction('api_buy_license');

        $this->assertCount(
            count($refBuy->getParameters()),
            $refPrepay->getParameters(),
            'api_buy_license_prepay should have same parameter count as api_buy_license'
        );

        $prepayParams = $refPrepay->getParameters();
        $buyParams = $refBuy->getParameters();

        for ($i = 0; $i < count($buyParams); $i++) {
            $this->assertSame(
                $buyParams[$i]->getName(),
                $prepayParams[$i]->getName(),
                "Parameter {$i} name mismatch"
            );
        }
    }

    /**
     * Test that api_change_license_ip has the correct parameter count.
     */
    public function testApiChangeLicenseIpParameterCount(): void
    {
        $ref = new ReflectionFunction('api_change_license_ip');
        $this->assertCount(3, $ref->getParameters());
    }

    /**
     * Test that api_change_license_ip has the correct parameter names.
     */
    public function testApiChangeLicenseIpParameterNames(): void
    {
        $ref = new ReflectionFunction('api_change_license_ip');
        $params = $ref->getParameters();
        $this->assertSame('sid', $params[0]->getName());
        $this->assertSame('oldip', $params[1]->getName());
        $this->assertSame('newip', $params[2]->getName());
    }

    /**
     * Test that api_change_license_ip_by_id has the correct parameter count.
     */
    public function testApiChangeLicenseIpByIdParameterCount(): void
    {
        $ref = new ReflectionFunction('api_change_license_ip_by_id');
        $this->assertCount(3, $ref->getParameters());
    }

    /**
     * Test that api_change_license_ip_by_id has the correct parameter names.
     */
    public function testApiChangeLicenseIpByIdParameterNames(): void
    {
        $ref = new ReflectionFunction('api_change_license_ip_by_id');
        $params = $ref->getParameters();
        $this->assertSame('sid', $params[0]->getName());
        $this->assertSame('id', $params[1]->getName());
        $this->assertSame('newip', $params[2]->getName());
    }

    /**
     * Test that all API functions are defined in the global namespace.
     */
    public function testAllFunctionsAreInGlobalNamespace(): void
    {
        foreach (self::$apiFunctions as $func) {
            $ref = new ReflectionFunction($func);
            $this->assertSame('', $ref->getNamespaceName(), "{$func} should be in the global namespace");
        }
    }

    /**
     * Test that all API functions that accept $sid have it as their first parameter.
     */
    public function testSidIsFirstParameterWhereApplicable(): void
    {
        $functionsWithSid = [
            'api_cancel_license_ip',
            'api_cancel_license',
            'api_buy_license_prepay',
            'api_buy_license',
            'api_change_license_ip',
            'api_change_license_ip_by_id',
        ];

        foreach ($functionsWithSid as $func) {
            $ref = new ReflectionFunction($func);
            $params = $ref->getParameters();
            $this->assertSame('sid', $params[0]->getName(), "{$func} should have \$sid as first parameter");
        }
    }

    /**
     * Test that the api.php source code references the 'licenses' module consistently.
     */
    public function testApiFileReferencesLicensesModule(): void
    {
        $path = dirname(__DIR__) . '/src/api.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringContainsString("'licenses'", $contents);
    }

    /**
     * Test that the api.php source code uses get_module_db calls.
     */
    public function testApiFileUsesGetModuleDb(): void
    {
        $path = dirname(__DIR__) . '/src/api.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('get_module_db', $contents);
    }

    /**
     * Test that the api.php source code uses get_module_settings calls.
     */
    public function testApiFileUsesGetModuleSettings(): void
    {
        $path = dirname(__DIR__) . '/src/api.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('get_module_settings', $contents);
    }

    /**
     * Test that api_buy_license_prepay delegates to api_buy_license based on source analysis.
     */
    public function testApiBuyLicensePrepayDelegatesToBuyLicense(): void
    {
        $ref = new ReflectionFunction('api_buy_license_prepay');
        $filename = $ref->getFileName();
        $this->assertNotFalse($filename);

        $startLine = $ref->getStartLine();
        $endLine = $ref->getEndLine();
        $this->assertNotFalse($startLine);
        $this->assertNotFalse($endLine);

        $source = file($filename);
        $this->assertNotFalse($source);

        $body = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));
        $this->assertStringContainsString('api_buy_license(', $body);
    }

    /**
     * Test that all API functions are defined in the same file.
     */
    public function testAllFunctionsDefinedInSameFile(): void
    {
        $files = [];
        foreach (self::$apiFunctions as $func) {
            $ref = new ReflectionFunction($func);
            $files[] = $ref->getFileName();
        }

        $uniqueFiles = array_unique($files);
        $this->assertCount(1, $uniqueFiles, 'All API functions should be in the same file');
    }
}

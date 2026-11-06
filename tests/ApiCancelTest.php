<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * The license cancel api must cancel only a live license and report what
 * cancel_service() did.
 *
 * Before: the ip lookup had no status filter, so with a canceled license and a
 * new active one on the same ip it picked the first row, and both api functions
 * answered "ok / License Canceled" whatever cancel_service() returned.
 */
class ApiCancelTest extends TestCase
{
    /** @var array<string, string> */
    private const SETTINGS = ['TABLE' => 'licenses', 'PREFIX' => 'license'];

    public function testIpLookupOnlyMatchesLiveLicensesOfTheCustomer(): void
    {
        $query = licenses_cancel_ip_query(self::SETTINGS, '192.0.2.10', '123');
        $this->assertSame(
            "select * from licenses where license_ip='192.0.2.10' and license_custid='123' and license_status not in ('canceled','expired')",
            $query
        );
        $this->assertStringContainsString("license_custid='0'", licenses_cancel_ip_query(self::SETTINGS, '192.0.2.10', "x' or '1'='1"));
    }

    public function testCanceledIsOk(): void
    {
        $this->assertSame(['status' => 'ok', 'status_text' => "License Canceled.\n"], licenses_cancel_api_result(true));
    }

    public function testRefusedOrFailedCancelIsAnError(): void
    {
        $result = licenses_cancel_api_result(false);
        $this->assertSame('error', $result['status']);
        $this->assertNotSame('', $result['status_text']);
        $this->assertStringNotContainsString('Canceled.', $result['status_text']);
    }

    /**
     * Both api functions answer with cancel_service()'s result, and the ip one
     * looks up through the status-filtered query.
     */
    public function testApiFunctionsUseTheResultOfCancelService(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__) . '/src/api.php');
        $this->assertSame(3, substr_count($source, 'return licenses_cancel_api_result(cancel_service($id, $module));'));
        $this->assertSame(3, substr_count($source, 'cancel_service($id, $module)'), 'no cancel_service() call ignores its result');
        $this->assertStringContainsString('$query = licenses_cancel_ip_query($settings, $ip, $custid);', $source);
    }
}

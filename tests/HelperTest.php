<?php

namespace OneGbits\Tests;

use OneGbits\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function testBaseUrlAppendsDefaultPathToBareHost(): void
    {
        $this->assertSame(
            'https://api.1gbits.com/v1',
            Helper::baseUrl(['serverhostname' => 'api.1gbits.com', 'serversecure' => 'on'])
        );
    }

    public function testBaseUrlUsesHttpWhenServerIsNotSecure(): void
    {
        $this->assertSame(
            'http://api.1gbits.com/v1',
            Helper::baseUrl(['serverhostname' => 'api.1gbits.com', 'serversecure' => ''])
        );
    }

    public function testBaseUrlHonoursExplicitPathAndScheme(): void
    {
        $this->assertSame(
            'https://api.1gbits.com/v2',
            Helper::baseUrl(['serverhostname' => 'https://api.1gbits.com/v2/', 'serversecure' => ''])
        );
    }

    public function testBaseUrlAppendsNonDefaultPortOnly(): void
    {
        $params = ['serverhostname' => 'api.1gbits.com', 'serversecure' => 'on'];

        $this->assertSame('https://api.1gbits.com:8443/v1', Helper::baseUrl($params + ['serverport' => 8443]));
        $this->assertSame('https://api.1gbits.com/v1', Helper::baseUrl($params + ['serverport' => 443]));
    }

    public function testBaseUrlFallsBackToServerIp(): void
    {
        $this->assertSame(
            'https://203.0.113.10/v1',
            Helper::baseUrl(['serverhostname' => '', 'serverip' => '203.0.113.10'])
        );
    }

    public function testBaseUrlRejectsEmptyServer(): void
    {
        $this->expectException(\RuntimeException::class);
        Helper::baseUrl([]);
    }

    public function testClientFromParamsRequiresCredentials(): void
    {
        $this->expectException(\RuntimeException::class);
        Helper::clientFromParams(['serverhostname' => 'api.1gbits.com', 'serverusername' => 'key']);
    }

    public function testClientFromParamsFallsBackFromAccessHashToPassword(): void
    {
        $client = Helper::clientFromParams([
            'serverhostname' => 'api.1gbits.com',
            'serverusername' => 'key',
            'serveraccesshash' => '',
            'serverpassword' => 'secret',
        ]);

        $this->assertInstanceOf(\OneGbits\ApiClient::class, $client);
    }

    public function testLogMaskCollectsNonEmptySecrets(): void
    {
        $mask = Helper::logMask([
            'serverusername' => 'key',
            'serverpassword' => '',
            'serveraccesshash' => 'hash',
        ]);

        $this->assertSame(['key', 'hash'], $mask);
    }

    public function testTermMonthsFromBillingCycle(): void
    {
        $this->assertSame(1, Helper::termMonthsFromBillingCycle('Monthly'));
        $this->assertSame(3, Helper::termMonthsFromBillingCycle('quarterly'));
        $this->assertSame(12, Helper::termMonthsFromBillingCycle('Annually'));
        $this->assertSame(36, Helper::termMonthsFromBillingCycle('triennially'));
        $this->assertSame(1, Helper::termMonthsFromBillingCycle('free account'));
        $this->assertSame(1, Helper::termMonthsFromBillingCycle(null));
    }

    public function testGenerateHostnameUsesServiceId(): void
    {
        $this->assertSame('srv-42.1gbits.local', Helper::generateHostname(['serviceid' => 42]));
    }

    public function testCustomFieldLookupIsCaseInsensitive(): void
    {
        $params = ['customfields' => ['RAID Level' => 'RAID10']];

        $this->assertSame('RAID10', Helper::customField($params, 'raid level'));
        $this->assertNull(Helper::customField($params, 'Missing'));
    }

    public function testExtractServerIdChecksKnownKeys(): void
    {
        $this->assertSame('abc', Helper::extractServerId(['serverId' => 'abc']));
        $this->assertSame('12', Helper::extractServerId(['data' => ['id' => 12]]));
        $this->assertSame('', Helper::extractServerId(['data' => []]));
    }

    public function testServerIdFallsBackToCustomField(): void
    {
        $this->assertSame('srv-1', Helper::serverId(['customfields' => ['Server ID' => 'srv-1']]));
        $this->assertSame('', Helper::serverId([]));
    }

    public function testStoreServerIdReturnsFalseWithoutServiceModel(): void
    {
        $this->assertFalse(Helper::storeServerId([], 'srv-1'));
    }

    public function testNormalizeDetailsHandlesBothPayloadShapes(): void
    {
        $expected = [
            'id' => '7',
            'status' => 'active',
            'hostname' => 'srv7.example.com',
            'location' => 'ist',
            'primaryIp' => '203.0.113.7',
            'planSku' => 'DEDI-INTEL-E3',
            'os' => 'ubuntu-24.04',
        ];

        $flat = [
            'id' => 7,
            'status' => 'active',
            'hostname' => 'srv7.example.com',
            'location' => 'ist',
            'primary_ip' => '203.0.113.7',
            'plan_sku' => 'DEDI-INTEL-E3',
            'os' => 'ubuntu-24.04',
        ];

        $this->assertSame($expected, Helper::normalizeDetails($flat));
        $this->assertSame($expected, Helper::normalizeDetails(['data' => $flat]));
    }

    public function testErrorTextPrefersApiError(): void
    {
        $this->assertSame('HTTP 422: bad plan', Helper::errorText(['error' => 'HTTP 422: bad plan']));
        $this->assertStringContainsString('Unexpected API response', Helper::errorText(['error' => '', 'data' => []]));
    }

    public function testSafeEscapesHtml(): void
    {
        $this->assertSame('&lt;b&gt;x&lt;/b&gt;', Helper::safe('<b>x</b>'));
    }
}

<?php

namespace OneGbits;

class Helper
{
    public const VERSION = '1.0.0';

    /** Service property used to persist the provisioned server identifier. */
    public const PROP_SERVER_ID = 'Server ID';

    /**
     * Build an API client from the WHMCS *server* record.
     *
     * Credentials live on the server (System Settings > Servers) rather than on
     * each product, so that TestConnection — which WHMCS invokes from the server
     * configuration page, where no product config options exist — works too.
     */
    public static function clientFromParams(array $params): ApiClient
    {
        $key = (string) ($params['serverusername'] ?? '');
        $secret = (string) ($params['serveraccesshash'] ?? '');

        if ($secret === '') {
            $secret = (string) ($params['serverpassword'] ?? '');
        }

        if ($key === '' || $secret === '') {
            throw new \RuntimeException(
                'API credentials are not configured. Set the API key as the server Username '
                . 'and the API secret as the server Access Hash in System Settings > Servers.'
            );
        }

        return new ApiClient(
            self::baseUrl($params),
            $key,
            $secret,
            'WHMCS-1Gbits/' . self::VERSION
        );
    }

    /**
     * Derive the API base URL from the server hostname/IP, SSL flag and port.
     *
     * The hostname field accepts a bare host ("api.1gbits.com"), a host with a
     * path ("api.1gbits.com/v2") or a full URL ("https://api.1gbits.com/v1").
     * When no path is given, {@see ApiClient::DEFAULT_BASE_PATH} is appended.
     */
    public static function baseUrl(array $params): string
    {
        $host = trim((string) ($params['serverhostname'] ?? ''));

        if ($host === '') {
            $host = trim((string) ($params['serverip'] ?? ''));
        }

        if ($host === '') {
            throw new \RuntimeException('No hostname or IP address is configured for this server.');
        }

        $scheme = '';
        if (preg_match('#^(https?)://#i', $host, $m)) {
            $scheme = strtolower($m[1]);
            $host = preg_replace('#^https?://#i', '', $host);
        }

        if ($scheme === '') {
            $scheme = self::isSecure($params) ? 'https' : 'http';
        }

        $host = rtrim($host, '/');
        $path = '';
        if (strpos($host, '/') !== false) {
            [$host, $path] = explode('/', $host, 2);
            $path = '/' . trim($path, '/');
        }

        if ($path === '') {
            $path = ApiClient::DEFAULT_BASE_PATH;
        }

        $port = (int) ($params['serverport'] ?? 0);
        $isDefaultPort = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
        $portPart = ($port > 0 && !$isDefaultPort && strpos($host, ':') === false) ? ':' . $port : '';

        return $scheme . '://' . $host . $portPart . $path;
    }

    private static function isSecure(array $params): bool
    {
        $secure = $params['serversecure'] ?? true;

        if (is_string($secure)) {
            return !in_array(strtolower($secure), ['', '0', 'off', 'no', 'false'], true);
        }

        return (bool) $secure;
    }

    /**
     * Values that must never reach the module log in clear text.
     *
     * @return string[]
     */
    public static function logMask(array $params): array
    {
        return array_values(array_filter([
            (string) ($params['serverusername'] ?? ''),
            (string) ($params['serverpassword'] ?? ''),
            (string) ($params['serveraccesshash'] ?? ''),
        ], static function ($value) {
            return $value !== '';
        }));
    }

    public static function safe($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function stringify($value): string
    {
        return (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /** Human readable failure text for a client result. */
    public static function errorText(array $result): string
    {
        $error = (string) ($result['error'] ?? '');

        return $error !== '' ? $error : 'Unexpected API response: ' . self::stringify($result['data'] ?? []);
    }

    public static function generateHostname(array $params): string
    {
        $id = (int) ($params['serviceid'] ?? 0);

        return 'srv-' . ($id > 0 ? $id : mt_rand(1000, 9999)) . '.1gbits.local';
    }

    public static function termMonthsFromBillingCycle(?string $cycle): int
    {
        $map = [
            'monthly' => 1,
            'quarterly' => 3,
            'semiannually' => 6,
            'semi-annually' => 6,
            'annually' => 12,
            'biennially' => 24,
            'triennially' => 36,
        ];

        return $map[strtolower(trim((string) $cycle))] ?? 1;
    }

    /** Fetch a service custom field value by name. */
    public static function customField(array $params, string $name)
    {
        foreach (($params['customfields'] ?? []) as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Read the provisioned server ID.
     *
     * Prefers the module service property written by CreateAccount, and falls
     * back to a "Server ID" custom field for services created before 1.0.0.
     */
    public static function serverId(array $params): string
    {
        $model = $params['model'] ?? null;

        if (is_object($model) && isset($model->serviceProperties)) {
            $stored = (string) $model->serviceProperties->get(self::PROP_SERVER_ID);

            if ($stored !== '') {
                return $stored;
            }
        }

        return trim((string) self::customField($params, self::PROP_SERVER_ID));
    }

    /**
     * Persist the server ID against the service so later module commands can
     * find it. Returns false when the WHMCS service model is unavailable.
     */
    public static function storeServerId(array $params, string $serverId): bool
    {
        $model = $params['model'] ?? null;

        if ($serverId === '' || !is_object($model) || !isset($model->serviceProperties)) {
            return false;
        }

        $model->serviceProperties->save([self::PROP_SERVER_ID => $serverId]);

        return true;
    }

    /** Extract the server identifier from a provisioning response. */
    public static function extractServerId(array $data): string
    {
        $candidates = [
            $data['serverId'] ?? null,
            $data['server_id'] ?? null,
            $data['id'] ?? null,
            $data['data']['serverId'] ?? null,
            $data['data']['server_id'] ?? null,
            $data['data']['id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_scalar($candidate) && (string) $candidate !== '') {
                return (string) $candidate;
            }
        }

        return '';
    }

    /** Normalise the server detail payload returned by the API. */
    public static function normalizeDetails(array $data): array
    {
        $server = $data['data'] ?? $data;

        if (!is_array($server)) {
            return [];
        }

        return [
            'id' => (string) ($server['id'] ?? $server['serverId'] ?? ''),
            'status' => (string) ($server['status'] ?? ''),
            'hostname' => (string) ($server['hostname'] ?? ''),
            'location' => (string) ($server['location'] ?? ''),
            'primaryIp' => (string) ($server['primaryIp'] ?? $server['primary_ip'] ?? ''),
            'planSku' => (string) ($server['planSku'] ?? $server['plan_sku'] ?? ''),
            'os' => (string) ($server['os'] ?? ''),
        ];
    }
}

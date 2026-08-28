<?php
namespace OneGbits;

class Helper
{
    public static function clientFromParams(array $params): ApiClient
    {
        $base = $params['configoption1'] ?? '';
        $key  = $params['configoption2'] ?? '';
        $sec  = $params['configoption3'] ?? '';
        if (!$base || !$key || !$sec) {
            throw new \RuntimeException('API credentials are not configured');
        }
        return new ApiClient($base, $key, $sec);
    }

    public static function safe($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function stringify($arr): string
    {
        return json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function generateHostname(array $params): string
    {
        $id = $params['serviceid'] ?? mt_rand(1000,9999);
        return 'srv-' . $id . '.1gbits.local';
    }

    public static function termMonthsFromBillingCycle(string $cycle): int
    {
        $map = [
            'monthly' => 1,
            'quarterly' => 3,
            'semiannually' => 6,
            'annually' => 12,
            'biennially' => 24,
            'triennially' => 36,
        ];
        return $map[strtolower($cycle)] ?? 1;
    }

    /** Fetch a custom field value by name (Product-level custom fields) */
    public static function customField(array $params, string $name)
    {
        $fields = $params['customfields'] ?? [];
        foreach ($fields as $k => $v) {
            if (strcasecmp($k, $name) === 0) { return $v; }
        }
        return null;
    }

    /** Service custom field that stores the provisioned Server ID */
    public static function serviceServerId(array $params)
    {
        // Create a Product custom field named "Server ID" (Admin Only) and set it after order (or via AfterModuleCreate hook)
        return self::customField($params, 'Server ID');
    }
}

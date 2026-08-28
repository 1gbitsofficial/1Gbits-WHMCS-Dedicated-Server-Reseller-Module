<?php

namespace OneGbits;

/**
 * Thin cURL wrapper around the 1Gbits reseller API.
 *
 * Requests are authenticated with an API key header plus an HMAC-SHA256
 * signature over "METHOD\nPATH\nBODY". Adjust {@see self::sign()} and
 * {@see self::headers()} if your account uses a different scheme.
 */
class ApiClient
{
    /** Default API version path appended when the server hostname carries no path. */
    public const DEFAULT_BASE_PATH = '/v1';

    /** @var string Fully qualified base URL, no trailing slash. */
    private $baseUrl;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $apiSecret;

    /** @var int */
    private $timeout;

    /** @var int */
    private $connectTimeout;

    /** @var string */
    private $userAgent;

    public function __construct(
        string $baseUrl,
        string $apiKey,
        string $apiSecret,
        string $userAgent = 'WHMCS-1Gbits',
        int $timeout = 60,
        int $connectTimeout = 15
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->userAgent = $userAgent;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
    }

    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    public function post(string $path, array $payload = []): array
    {
        return $this->request('POST', $path, $payload);
    }

    public function delete(string $path, array $payload = []): array
    {
        return $this->request('DELETE', $path, $payload);
    }

    /**
     * @return array{success:bool,status:int,data:array,error:string,raw:string}
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        $body = $payload === [] ? '' : (string) json_encode($payload);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->headers($method, $path, $body),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => $this->userAgent,
        ]);

        if ($body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);

        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);

            return $this->result(false, 0, [], 'cURL error: ' . $error, '');
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode((string) $raw, true);
        $data = is_array($decoded) ? $decoded : [];

        if ($status >= 200 && $status < 300) {
            return $this->result(true, $status, $data, '', (string) $raw);
        }

        return $this->result(false, $status, $data, $this->errorMessage($status, $data, (string) $raw), (string) $raw);
    }

    /**
     * @return string[]
     */
    private function headers(string $method, string $path, string $body): array
    {
        return [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-API-KEY: ' . $this->apiKey,
            'X-SIGNATURE: ' . $this->sign($method, $path, $body),
        ];
    }

    private function sign(string $method, string $path, string $body): string
    {
        return hash_hmac('sha256', $method . "\n" . $path . "\n" . $body, $this->apiSecret);
    }

    private function errorMessage(int $status, array $data, string $raw): string
    {
        foreach (['message', 'error', 'detail'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && $data[$key] !== '') {
                return 'HTTP ' . $status . ': ' . $data[$key];
            }
        }

        $snippet = trim(substr($raw, 0, 300));

        return 'HTTP ' . $status . ($snippet === '' ? '' : ': ' . $snippet);
    }

    /**
     * @return array{success:bool,status:int,data:array,error:string,raw:string}
     */
    private function result(bool $success, int $status, array $data, string $error, string $raw): array
    {
        return [
            'success' => $success,
            'status' => $status,
            'data' => $data,
            'error' => $error,
            'raw' => $raw,
        ];
    }
}

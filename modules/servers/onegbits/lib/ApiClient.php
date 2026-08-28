<?php
namespace OneGbits;

class ApiClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;

    public function __construct(string $baseUrl, string $apiKey, string $apiSecret)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function get(string $path)
    {
        return $this->request('GET', $path);
    }

    public function post(string $path, array $payload)
    {
        return $this->request('POST', $path, $payload);
    }

    public function delete(string $path)
    {
        return $this->request('DELETE', $path);
    }

    private function request(string $method, string $path, array $payload = [])
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init();
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-API-KEY: ' . $this->apiKey,
            'X-SIGNATURE: ' . $this->sign($method, $path, $payload),
        ];
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload);
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return [ 'success' => false, 'error' => 'cURL error: ' . $err ];
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($raw, true);
        if ($code >= 200 && $code < 300) {
            return [ 'success' => true, 'data' => $data, 'status' => $code ];
        }
        return [ 'success' => false, 'status' => $code, 'response' => $data, 'raw' => $raw ];
    }

    private function sign(string $method, string $path, array $payload = []) : string
    {
        $body = $method === 'POST' ? json_encode($payload) : '';
        $msg = $method . "\n" . $path . "\n" . $body;
        return hash_hmac('sha256', $msg, $this->apiSecret);
    }
}

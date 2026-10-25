<?php

namespace MiCompress;

/**
 * Thin client for the MiCompress compression API (https://micompress.com).
 *
 *     $client = new \MiCompress\Client();
 *     $result = $client->compressFile('photo.jpg', 'photo-min.jpg');
 *     echo $result['savedPercent'] . "% smaller\n";
 */
class Client
{
    public const VERSION = '0.1.1';
    public const DEFAULT_BASE = 'https://micompress.com';
    /** Where users get a personal API key, and where to upgrade when its quota runs out. */
    public const ACCOUNT_URL = self::DEFAULT_BASE . '/account';
    public const PLANS_URL = self::DEFAULT_BASE . '/plans';

    /** @var string */
    private $baseUrl;
    /** @var string|null */
    private $token;
    /** @var string|null */
    private $apiKey;
    /** @var int */
    private $timeout;

    /**
     * @param array{baseUrl?: string, apiKey?: string, token?: string, timeout?: int} $options
     *   apiKey  personal API key ("mc_...") from micompress.com/account (optional)
     *   token   legacy JWT login token (optional; prefer apiKey)
     *   timeout seconds per request (default 120)
     */
    public function __construct(array $options = [])
    {
        $this->baseUrl = rtrim($options['baseUrl'] ?? self::DEFAULT_BASE, '/');
        $this->token = $options['token'] ?? null;
        $this->apiKey = $options['apiKey'] ?? null;
        $this->timeout = (int) ($options['timeout'] ?? 120);
    }

    /**
     * Compress a file and return the compressed bytes plus metadata.
     *
     * @param array{preset?: string, maxWidth?: int} $options
     *   preset   lossless | recommended | extreme
     *   maxWidth image resize cap in pixels
     * @return array{bytes: string, inputSize: int, outputSize: int, savedBytes: int, savedPercent: int, format: ?string, category: string}
     * @throws CompressException
     */
    public function compress(string $path, array $options = []): array
    {
        $filename = basename($path);
        $category = Categories::categoryFor($filename);
        if ($category === null) {
            throw new CompressException(
                sprintf('Unsupported file type ".%s". See %s/all-tools', Categories::extOf($filename) ?: '?', $this->baseUrl),
                'UNSUPPORTED'
            );
        }
        if (!is_file($path)) {
            throw new CompressException('File not found: ' . $path, 'ENOENT');
        }

        $fields = ['file' => new \CURLFile($path, 'application/octet-stream', $filename)];
        if (!empty($options['preset'])) {
            $fields['preset'] = (string) $options['preset'];
        }
        if (!empty($options['maxWidth'])) {
            $fields['maxWidth'] = (string) (int) $options['maxWidth'];
        }

        $url = $this->baseUrl . '/' . Categories::CATEGORY_PATH[$category] . '/compress';
        $headers = ['Accept: application/json'];
        // The API key only authorises the upload; the download link needs no key.
        if ($this->apiKey) {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }
        [$status, $body] = $this->request($url, $fields, $headers);

        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new CompressException(sprintf('Unexpected response (HTTP %d)', $status), 'BADRESPONSE');
        }
        if ($status >= 400 || empty($json['success'])) {
            $code = $json['code'] ?? 'API_ERROR';
            if ($status === 401 && $code === 'LOGIN_REQUIRED') {
                throw new CompressException(
                    'File exceeds the anonymous size limit. Pass an API key (apiKey option / --api-key; free key at ' . self::ACCOUNT_URL . ').',
                    'LOGIN_REQUIRED'
                );
            }
            if ($code === 'INVALID_API_KEY') {
                throw new CompressException(self::withHint($json['error'] ?? 'Invalid API key.', 'Check your key at', self::ACCOUNT_URL), $code);
            }
            if ($code === 'API_QUOTA_REACHED' || $code === 'USAGE_LIMIT_REACHED') {
                throw new CompressException(
                    self::withHint($json['error'] ?? 'Monthly compression quota reached.', 'Upgrade for a bigger monthly quota:', self::PLANS_URL),
                    $code
                );
            }
            throw new CompressException($json['error'] ?? ('HTTP ' . $status), $code);
        }

        [$dlStatus, $bytes] = $this->request($this->baseUrl . $json['downloadUrl']);
        if ($dlStatus >= 400 || $bytes === '') {
            throw new CompressException(sprintf('Download failed (HTTP %d)', $dlStatus), 'DOWNLOAD');
        }

        return [
            'bytes' => $bytes,
            'inputSize' => (int) ($json['inputSize'] ?? 0),
            'outputSize' => (int) ($json['outputSize'] ?? strlen($bytes)),
            'savedBytes' => (int) ($json['savedBytes'] ?? 0),
            'savedPercent' => (int) ($json['savedPercent'] ?? 0),
            'format' => $json['format'] ?? null,
            'category' => $category,
        ];
    }

    /**
     * Compress $input and write the result to $output (default: "<name>-min.<ext>" next to the input).
     * Returns the same metadata as compress(), with 'bytes' replaced by 'output' (the written path).
     *
     * @throws CompressException
     */
    public function compressFile(string $input, ?string $output = null, array $options = []): array
    {
        $result = $this->compress($input, $options);
        if ($output === null) {
            $ext = pathinfo($input, PATHINFO_EXTENSION);
            $output = preg_replace('/\.' . preg_quote($ext, '/') . '$/', '', $input) . '-min' . ($ext !== '' ? '.' . $ext : '');
        }
        if (file_put_contents($output, $result['bytes']) === false) {
            throw new CompressException('Could not write ' . $output, 'WRITE');
        }
        unset($result['bytes']);
        $result['output'] = $output;
        return $result;
    }

    /**
     * @param array<string, mixed>|null $postFields
     * @param string[] $headers
     * @return array{0: int, 1: string}
     */
    /** Append "<lead> <url>" unless the server's message already names the URL. */
    private static function withHint(string $message, string $lead, string $url): string
    {
        return strpos($message, preg_replace('#^https?://#', '', $url)) !== false ? $message : "$message $lead $url";
    }

    private function request(string $url, ?array $postFields = null, array $headers = []): array
    {
        $headers[] = 'X-MiCompress-Client: packagist/' . self::VERSION;
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            // Branded UA identifies the client and stays clear of WAF bot rules.
            CURLOPT_USERAGENT => 'micompress-php/' . self::VERSION . ' (+https://micompress.com)',
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($postFields !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new CompressException('Network error: ' . $err, 'NETWORK');
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return [$status, (string) $body];
    }
}

<?php declare (strict_types = 1);

namespace App\Services\Providers;

use App\Contracts\LookupProviderInterface;
use App\Services\ApiCacheService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class XblProvider implements LookupProviderInterface
{
    private Client $client;
    private ApiCacheService $apiCache;
    private const BASE         = 'https://ident.tebex.io/usernameservices/3/username/';
    private const CACHE_PREFIX = 'xbl_';

    public function __construct(Client $client, ApiCacheService $apiCache)
    {
        $this->client   = $client;
        $this->apiCache = $apiCache;
    }

    public function findByUsername(string $username): ?array
    {
        $cacheKey = self::CACHE_PREFIX . "user_{$username}";

        return $this->apiCache->rememberApi($cacheKey, function () use ($username) {
            try {
                $response = $this->client->get(self::BASE . rawurlencode($username) . '?type=username');
                $data     = $this->parseResponse((string) $response->getBody()->getContents());

                return $this->formatResult($data);
            } catch (GuzzleException $e) {
                Log::error('Request failed', ['error' => $e->getMessage()]);
                throw $e;
            }

        });
    }

    public function findByUserId(string $id): ?array
    {
        $cacheKey = self::CACHE_PREFIX . "id_{$id}";

        return $this->apiCache->rememberApi($cacheKey, function () use ($id) {
            try {
                $response = $this->client->get(self::BASE . rawurlencode($id));
                $data     = $this->parseResponse((string) $response->getBody()->getContents());

                return $this->formatResult($data);
            } catch (GuzzleException $e) {
                Log::error('Request failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        });
    }

    private function parseResponse(string $response): ?object
    {
        $data = json_decode($response);

        if (! isset($data->id, $data->username)) {
            return null;
        }

        return $data;
    }

    private function formatResult(?object $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return [
            'username' => $data->username,
            'id'       => $data->id,
            'avatar'   => $data->meta->avatar ?? '',
        ];
    }
}

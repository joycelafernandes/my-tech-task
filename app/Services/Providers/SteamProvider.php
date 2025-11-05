<?php declare (strict_types = 1);

namespace App\Services\Providers;

use App\Contracts\LookupProviderInterface;
use App\Services\ApiCacheService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class SteamProvider implements LookupProviderInterface
{
    private Client $client;
    protected $apiCache;
    private const BASE = 'https://ident.tebex.io/usernameservices/4/username/';

    public function __construct(Client $client, ApiCacheService $apiCache)
    {
        $this->client   = $client;
        $this->apiCache = $apiCache;
    }

    public function findByUsername(string $username): ?array
    {
        return ['error' => 'Steam only supports IDs'];
    }

    public function findByUserId(string $id): ?array
    {
        $cacheKey = "steam_user_{$id}";

        return $this->apiCache->rememberApi($cacheKey, function () use ($id) {
            try {
                $response = $this->client->get(self::BASE . rawurlencode($id));
                $data     = json_decode((string) $response->getBody(), false);

                if (! isset($data->id, $data->username)) {
                    return null;
                }

                return [
                    'username' => $data->username,
                    'id'       => $data->id,
                    'avatar'   => $data->meta->avatar ?? '',
                ];

            } catch (GuzzleException $e) {
                Log::error('Request failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        });
    }
}

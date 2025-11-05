<?php declare (strict_types = 1);

namespace App\Services\Providers;

use App\Contracts\LookupProviderInterface;
use App\Services\ApiCacheService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Steam Lookup Provider
 */
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

    /**
     * Find user by username.
     *
     * @param string $username
     * @return array|null
     */
    public function findByUsername(string $username): ?array
    {
        return ['error' => 'Steam only supports IDs'];
    }

    /**
     * Find user by user ID.
     *
     * @param string $id
     * @return array|null
     */
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

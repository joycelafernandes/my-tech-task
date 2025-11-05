<?php declare (strict_types = 1);

namespace App\Services\Providers;

use App\Contracts\LookupProviderInterface;
use App\Services\ApiCacheService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Minecraft Lookup Provider
 */
class MinecraftProvider implements LookupProviderInterface
{
    private Client $client;
    protected $apiCache;
    private const AvatarBase   = 'https://crafatar.com/avatars/';
    private const CACHE_PREFIX = 'minecraft_';

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
        $cacheKey = self::CACHE_PREFIX . "profile_{$username}";

        return $this->apiCache->rememberApi($cacheKey, function () use ($username) {
            try {
                $response = $this->client->get("https://api.mojang.com/users/profiles/minecraft/{$username}");
                $data     = $this->parseResponse((string) $response->getBody()->getContents());

                return $this->formatResult($data);
            } catch (GuzzleException $e) {
                Log::error('Request failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        });

    }

    /**
     * Find user by user ID.
     *
     * @param string $id
     * @return array|null
     */
    public function findByUserId(string $id): ?array
    {
        $cacheKey = self::CACHE_PREFIX . "user_{$id}";

        return $this->apiCache->rememberApi($cacheKey, function () use ($id) {
            try {

                $response = $this->client->get("https://sessionserver.mojang.com/session/minecraft/profile/{$id}");
                $data     = $this->parseResponse((string) $response->getBody()->getContents());

                return $this->formatResult($data);
            } catch (GuzzleException $e) {
                Log::error('Request failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        });
    }

    /**
     * Parse the API response.
     *
     * @param string $response
     * @return object|null
     */
    private function parseResponse(string $response): ?object
    {
        $data = json_decode($response);

        if (! isset($data->id, $data->name)) {
            Log::warning('Response missing required fields', ['response' => $response]);
            return null;
        }

        return $data;
    }

    /**
     * Format the result into a consistent array structure.
     *
     * @param object|null $data
     * @return array|null
     */
    private function formatResult(?object $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return [
            'username' => $data->name,
            'id'       => $data->id,
            'avatar'   => self::AvatarBase . $data->id ?? '',
        ];
    }
}

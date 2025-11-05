<?php declare(strict_types=1);

namespace App\Services;

use App\Contracts\LookupProviderInterface;
use InvalidArgumentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LookupService
{
    private array $lookupProviders;

    /** 
     * @var array<string, LookupProviderInterface> 
     */
    public function __construct(array $lookupProviders)
    {
        $this->lookupProviders = $lookupProviders;
    }

    public function lookup(string $type, array $params): ?array
    {
        try {
            $type = strtolower($type);

            if (! isset($this->lookupProviders[$type])) {
                throw new InvalidArgumentException('Unsupported lookup type');
            }

            $provider = $this->lookupProviders[$type];

            if (! empty($params['username'])) {
                return $provider->findByUsername((string) $params['username']);
            }

            if (! empty($params['id'])) {
                return $provider->findByUserId((string) $params['id']);
            }

            throw new InvalidArgumentException('Username or ID required');


        } catch (InvalidArgumentException $e) {
            Log::error('Invalid type provided', ['error' => $e->getMessage(), 'type' => $type]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
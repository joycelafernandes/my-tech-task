<?php
namespace Tests\Unit;

use App\Services\ApiCacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ApiCacheServiceTest extends TestCase
{
    private ApiCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiCacheService();
    }

    public function testFailedRequestsCacheForShorterTime()
    {
        Cache::shouldReceive('has')
            ->once()
            ->with('test-key')
            ->andReturn(false);

        Cache::shouldReceive('remember')
            ->once()
            ->with('test-key', 600, \Closure::class)
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Cache::shouldReceive('put')
            ->once()
            ->with('test-key', null, 60);

        $result = $this->service->rememberApi('test-key', function () {
            return null;
        }, 600, 60);

        $this->assertNull($result);
    }

    public function testSuccessfulRequestsCacheForLongerTime()
    {
        $testData = ['data' => 'test'];

        Cache::shouldReceive('has')
            ->once()
            ->with('test-key')
            ->andReturn(false);

            Cache::shouldReceive('remember')
            ->once()
            ->with('test-key', 600, \Closure::class)
            ->andReturnUsing(function ($key, $ttl, $callback) use ($testData) {
                return $callback();
            });

            $result = $this->service->rememberApi('test-key', function() use ($testData) {
            return $testData;
        }, 600, 60);

        $this->assertEquals($testData, $result);
    }
}

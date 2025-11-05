<?php declare(strict_types=1);

namespace App\Providers;

use App\Services\LookupService;
use App\Services\Providers\MinecraftProvider;
use App\Services\Providers\SteamProvider;
use App\Services\Providers\XblProvider;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class LookupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return new Client(['timeout' => 5.0]);
        });

        $this->app->singleton(MinecraftProvider::class);
        $this->app->singleton(SteamProvider::class);
        $this->app->singleton(XblProvider::class);

        $this->app->singleton(LookupService::class, function ($app) {
            return new LookupService([
                'minecraft' => $app->make(MinecraftProvider::class),
                'steam' => $app->make(SteamProvider::class),
                'xbl' => $app->make(XblProvider::class),
            ]);
        });
    }
}
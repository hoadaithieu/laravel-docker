<?php

namespace App\Providers;

use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Support\ServiceProvider;

class DynamoDBServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(DynamoDbClient::class, function ($app) {
            return new DynamoDbClient(config('aws'));
        });
    }

    public function boot()
    {
        //
    }
}

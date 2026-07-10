<?php

namespace App\Providers;

use App\Search\SearchParametersFactory;
use Elastic\ScoutDriver\Factories\SearchParametersFactoryInterface;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SearchParametersFactoryInterface::class, SearchParametersFactory::class);
    }
}

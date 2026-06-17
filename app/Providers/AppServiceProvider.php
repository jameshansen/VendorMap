<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Make the admin-curated category suggestions available everywhere the
        // shared vendor-fields partial is rendered (sign-up, profile, Google).
        View::composer('partials.vendor-fields', function ($view) {
            $suggestions = Schema::hasTable('categories')
                ? Category::orderBy('name')->pluck('name')->all()
                : [];
            $view->with('categorySuggestions', $suggestions);
        });
    }
}

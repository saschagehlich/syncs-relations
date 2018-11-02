<?php

namespace SyncsRelations\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class TestServiceProvider extends BaseServiceProvider
{
    protected $defer = false;
    public function boot()
    {
        $this->loadMigrationsFrom(
            __DIR__ . '/database/migrations'
        );

        DB::listen(function($query) {
//            echo '(' . $query->time . 'ms) ' . $query->sql . ' (' . implode(', ', $query->bindings) . ")\n";
        });
    }
}
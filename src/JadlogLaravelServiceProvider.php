<?php namespace  Tuliovgomes\LaravelJadlog;

use Illuminate\Support\ServiceProvider;

class JadlogLaravelServiceProvider extends ServiceProvider
{
    /**
     * Register the LaravelJadlog class
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('LaravelJadlgo', function () {
            return new \Tuliovgomes\LaravelJadlog\Builder\LaravelJadlog;
        });
    }
}

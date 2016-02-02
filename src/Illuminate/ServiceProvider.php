<?php
namespace KageNoNeko\OSM\Illuminate;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Path to package config
     *
     * @var string
     */
    protected $configPath;

    public function __construct($app) {
        parent::__construct($app);
        $this->configPath = __DIR__ . '/../../config/osm.php';
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot() {
        $this->publishes([$this->configPath => config_path('osm.php')], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->mergeConfigFrom($this->configPath, 'osm');

        $this->app->singleton('osm.factory', function ($app) {
            return new ConnectionFactory();
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('osm', function ($app) {
            return new Manager($app, $app['osm.factory']);
        });

        $this->app->bind('osm.connection', function ($app) {
            return $app['osm']->connection();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {

        return [];
    }
}

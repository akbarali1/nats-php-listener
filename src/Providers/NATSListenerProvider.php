<?php
declare(strict_types=1);

namespace Akbarali\NatsListener\Providers;

use Akbarali\NatsListener\Console\InstallCommand;
use Akbarali\NatsListener\Console\NatsChannelListener;
use Akbarali\NatsListener\Console\TerminateCommand;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class NATSListenerProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		$this->registerCommands();
		$this->offerPublishing();
		$this->registerRoutes();
	}
	
	protected function registerRoutes(): void
	{
		if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
			return;
		}
		
		Route::group([
			'domain'     => config('nats.domain', null),
			'prefix'     => config('nats.path'),
			'middleware' => config('nat.middleware', 'web'),
		], function () {
			$this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
		});
	}
	
	protected function registerCommands(): void
	{
		if ($this->app->runningInConsole()) {
			$this->commands([
				InstallCommand::class,
				NatsChannelListener::class,
				TerminateCommand::class,
			]);
		}
	}
	
	protected function offerPublishing(): void
	{
		if ($this->app->runningInConsole()) {
			$this->publishes([
				__DIR__.'/../../config/nats.php' => config_path('nats.php'),
			], 'nats-config');
			
			$this->publishes([
				__DIR__.'/../../lang/eng/exceptions.php' => lang_path('eng/exceptions.php'),
			], 'nats-lang');
			
			$this->publishes([
				__DIR__.'/../../routes/nats.php' => base_path('routes/nats.php'),
			], 'nats-route');
		}
	}
	
	public function register(): void
	{
		if (!defined('NATS_LISTENER_PATH')) {
			define('NATS_LISTENER_PATH', dirname(__DIR__).'/');
		}
		
		$this->mergeConfigFrom(
			path: __DIR__.'/../../config/nats.php',
			key : 'nats'
		);
	}
	
}

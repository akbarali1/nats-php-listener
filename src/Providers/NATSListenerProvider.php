<?php
declare(strict_types=1);

namespace Akbarali\NatsListener\Providers;

use Akbarali\NatsListener\Console\InstallCommand;
use Akbarali\NatsListener\Console\NatsChannelListener;
use Akbarali\NatsListener\Console\TerminateCommand;
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
				__DIR__.'/../config/nats.php' => config_path('nats.php'),
			], 'nats-config');
			
			$this->publishes([
				__DIR__.'/../lang/eng/exceptions.php' => lang_path('eng/exceptions.php'),
			], 'nats-lang');
		}
	}
	
}

<?php

namespace Akbarali\NatsListener\Console;

use Akbarali\NatsListener\Providers\NATSListenerProvider;
use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
	
	protected $signature = 'nats:install';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Install all of the Nats Listener resources';
	
	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function handle(): void
	{
		$this->components->info('Installing Nats Listener resources.');
		collect([
			'Configuration' => fn() => (int) $this->callSilent('vendor:publish', ['--tag' => 'nats-config']) === 0,
			'Lang'          => fn() => (int) $this->callSilent('vendor:publish', ['--tag' => 'nats-lang']) === 0,
			'Routes'        => fn() => (int) $this->callSilent('vendor:publish', ['--tag' => 'nats-route']) === 0,
		])->each(fn($task, $description) => $this->components->task($description, $task));
		
		//$this->registerNatsServiceProvider();
		
		$this->components->info('NATS Listener installed successfully.');
	}
	
	protected function registerNatsServiceProvider(): void
	{
		$namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());
		$provider  = NATSListenerProvider::class."::class";
		
		if (file_exists($this->laravel->bootstrapPath('providers.php'))) {
			ServiceProvider::addProviderToBootstrapFile($provider.PHP_EOL);
		} else {
			$appConfig = file_get_contents(config_path('app.php'));
			if (Str::contains($appConfig, $provider)) {
				return;
			}
			file_put_contents(config_path('app.php'), str_replace(
				"{$namespace}\\Providers\RouteServiceProvider::class,".PHP_EOL,
				"{$namespace}\\Providers\RouteServiceProvider::class,".PHP_EOL."        $provider,".PHP_EOL,
				$appConfig
			));
		}
		
	}
}

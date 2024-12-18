<?php

namespace Akbarali\NatsListener\Console;

use Akbarali\NatsListener\Dispatchers\NatsChannelDispatcher;
use Akbarali\NatsListener\Exceptions\InternalException;
use Akbarali\NatsListener\Exceptions\NatsException;
use Akbarali\NatsListener\Managers\CacheManager;
use Akbarali\NatsListener\Presenters\NatsApiResponse;
use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Basis\Nats\Message\Payload;
use Closure;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NatsChannelListener extends Command
{
	#region Properties
	protected               $signature   = 'nats:listener';
	protected               $description = 'Start to listening nats';
	protected string        $connectionName;
	protected Configuration $configuration;
	protected Client        $client;
	protected Closure       $callback;
	protected Collection    $availableLocales;
	protected bool          $working     = true;
	
	#endregion
	protected array $routes;
	
	public function __construct(
		protected CacheManager $cacheManager
	) {
		$config                 = config('nats', []);
		$this->availableLocales = collect($config['available_locales'] ?? []);
		$natsConfiguration      = $config['configuration'] ?? [];
		if (!app()->isLocal()) {
			$this->configuration = new Configuration($natsConfiguration);
			$this->configuration->setDelay($config['connection']['delay'] ?? 1);
			$this->connectionName = $config['connection']['name'] ?? null;
			$this->createClient();
		}
		parent::__construct();
	}
	
	protected function createClient(): void
	{
		$this->client = new Client($this->configuration);
		if (($this->connectionName) !== null) {
			$this->client->setName($this->connectionName);
		}
	}
	
	/**
	 * @throws NatsException
	 */
	protected function setSubscriptions(): void
	{
		$routeNames = array_keys($this->routes);
		if (count($routeNames) === 0) {
			throw NatsException::noRoutes();
		}
		
		foreach ($routeNames as $routeName) {
			$this->client->subscribe($routeName, $this->callback);
		}
	}
	
	protected function connect()
	{
		$this->createClient();
		$this->setSubscriptions();
	}
	
	/**
	 * @throws Exception
	 */
	public function handle(): void
	{
		$this->initRoutes();
		$this->initCallback();
		$this->cacheManager->setCache(getmypid());
		
		// флаг остановки
		$shallStopWorking = false;
		$this->listenForSignals($shallStopWorking);
		$this->setSubscriptions();
		
		$this->info("{$this->signature} - {$this->connectionName} -- started");
		try {
			while (!$shallStopWorking) {
				pcntl_signal_dispatch();
				if ($this->working) {
					$this->client->process();
				}
			}
			$this->info("{$this->signature} - {$this->connectionName} -- end");
		} catch (\Throwable $exception) {
			Log::error($exception);
			$this->error($exception->getMessage());
			$this->info("{$this->signature} - {$this->connectionName} -- error: {$exception->getMessage()}");
		} finally {
			$this->client->disconnect();
			$this->cacheManager->forgetCache(getmypid());
		}
	}
	
	protected function listenForSignals(bool &$shallStopWorking): void
	{
		// сигнал об остановке от supervisord
		pcntl_signal(SIGTERM, function () use (&$shallStopWorking) {
			$this->info("Received SIGTERM\n");
			$shallStopWorking = true;
		});
		
		// Close Terminal
		pcntl_signal(SIGHUP, function () use (&$shallStopWorking) {
			$this->info("Received SIGTERM\n");
			$shallStopWorking = true;
		});
		
		// обработчик для ctrl+c
		pcntl_signal(SIGINT, function () use (&$shallStopWorking) {
			$this->info("Received SIGINT\n");
			$shallStopWorking = true;
		});
		
		// Pause Process
		pcntl_signal(SIGUSR2, function () {
			$this->working = false;
			$this->client->disconnect();
			$this->info("Connection close\n");
		});
		
		// Continue Process
		pcntl_signal(SIGCONT, function () {
			try {
				$this->connect();
				$this->working = true;
				$this->info("Connection open\n");
			} catch (InternalException $e) {
				$this->info("Connection open failed: ".$e->getMessage().PHP_EOL);
			}
		});
	}
	
	protected function initCallback(): void
	{
		$this->callback = function (Payload $payload): Payload {
			$locale = $payload->getHeader('w-locale') ?? app()->getLocale();
			$auth   = $payload->getHeader('w-auth');
			try {
				$params = json_decode($payload->body ?? "{}", true, 512, JSON_THROW_ON_ERROR);
			} catch (\Throwable $t) {
				$params = $payload->body;
			}
			$route = $payload->subject;
			if (in_array($locale, $this->availableLocales->toArray(), true)) {
				app()->setLocale($locale);
			} else {
				app()->setLocale(app()->getLocale());
			}
			
			if (empty($route)) {
				$natsApiResponse = NatsApiResponse::createNatsError(NatsException::requestMethodNotFound());
				DB::reconnect();
				
				return $this->response($natsApiResponse, $payload);
			}
			
			$dispatcher      = new NatsChannelDispatcher($auth, $route, $this->routes, $params);
			$natsApiResponse = $dispatcher->call();
			DB::reconnect();
			
			return $this->response($natsApiResponse, $payload);
		};
	}
	
	/**
	 * @throws NatsException
	 */
	private function initRoutes(): void
	{
		if (!file_exists(base_path('routes/nats.php'))) {
			throw NatsException::routeFileNotFound();
		}
		
		$this->routes = require base_path('routes/nats.php');
	}
	
	/**
	 * @throws \JsonException
	 */
	protected function response(NatsApiResponse $res, Payload $payload): Payload
	{
		return new Payload(
			body   : $res->toJson(),
			headers: [
				"Content-Type" => "application/json",
				"w-auth"       => $payload->getHeader('w-auth'),
				"w-locale"     => $payload->getHeader('w-locale'),
			],
		);
	}
	
}

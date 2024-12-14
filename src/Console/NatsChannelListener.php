<?php

namespace Akbarali\NatsListener\Console;

use Akbarali\NatsListener\Dispatchers\NatsChannelDispatcher;
use Akbarali\NatsListener\Exceptions\NatsException;
use Akbarali\NatsListener\Presenters\NatsApiResponse;
use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Basis\Nats\Message\Payload;
use Closure;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NatsChannelListener extends Command
{
	#region Properties
	protected               $signature      = 'nats:listener';
	protected               $description    = 'Start to listening nats';
	protected string        $connectionName = 'cabinet';
	protected Configuration $connection;
	protected Client        $client;
	protected Closure       $callback;
	protected Collection    $availableLocales;
	
	#endregion
	protected array $routes;
	
	/**
	 * @throws NatsException
	 */
	public function __construct()
	{
		$config                 = file_exists(config_path('nats.php')) ? require config_path('nats.php') : [];
		$this->availableLocales = collect($config['available_locales'] ?? []);
		$natsConfiguration      = $config['configuration'] ?? [];
		if (!app()->isLocal()) {
			$this->connection = new Configuration($natsConfiguration);
			$this->connection->setDelay($config['connection']['delay'] ?? 1);
			$this->client = new Client($this->connection);
			if (($config['connection']['name'] ?? null) !== null) {
				$this->client->setName($config['connection']['name']);
			}
		}
		$this->initRoutes();
		$this->initCallback();
		parent::__construct();
	}
	
	/**
	 * @throws Exception
	 */
	public function handle(): void
	{
		$runningId = [getmypid()];
		if (Cache::has('nats:channel:config')) {
			$config     = Cache::get('nats:channel:config');
			$processIds = $config['pids'] ?? null;
			if ($processIds) {
				$processIds = array_merge($processIds, $runningId);
			}
		} else {
			$processIds = $runningId;
		}
		
		Cache::put('nats:channel:config', [
			'pids' => $processIds,
		]);
		
		// флаг остановки
		$shallStopWorking = false;
		
		// сигнал об остановке от supervisord
		pcntl_signal(SIGTERM, function () use (&$shallStopWorking) {
			$this->info("Received SIGTERM\n");
			$shallStopWorking = true;
		});
		
		// обработчик для ctrl+c
		pcntl_signal(SIGINT, function () use (&$shallStopWorking) {
			$this->info("Received SIGINT\n");
			$shallStopWorking = true;
		});
		
		$routeNames = array_keys($this->routes);
		if (count($routeNames) === 0) {
			throw NatsException::noRoutes();
		}
		
		foreach ($routeNames as $routeName) {
			$this->client->subscribe($routeName, $this->callback);
		}
		
		$this->info("{$this->signature} - {$this->connectionName} -- started");
		try {
			while (!$shallStopWorking) {
				pcntl_signal_dispatch();
				$this->client->process();
			}
			$config     = Cache::get('nats:channel:config');
			$processIds = $config['pids'] ?? [];
			if (count($processIds) === 1) {
				Cache::forget('nats:channel:config');
			} else {
				Cache::put('nats:channel:config', [
					'pids' => array_filter($processIds, static fn($id): bool => $id !== $runningId[0]),
				]);
			}
			$this->info("{$this->signature} - {$this->connectionName} -- end");
		} catch (\Throwable $exception) {
			Log::error($exception);
			$this->error($exception->getMessage());
		} finally {
			$this->client->disconnect();
		}
	}
	
	protected function initCallback(): void
	{
		$this->callback = function (Payload $payload): Payload {
			$locale      = $payload->getHeader('w-locale') ?? app()->getLocale();
			$auth        = $payload->getHeader('w-auth');
			$requestBody = json_decode($payload->body ?? "{}", true, 512, JSON_THROW_ON_ERROR);
			$route       = $payload->subject;
			$params      = data_get($requestBody, 'params', []);
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

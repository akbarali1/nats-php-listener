<?php
declare(strict_types=1);

namespace Akbarali\NatsListener\Dispatchers;

use Akbarali\ActionData\ActionDataBase;
use Akbarali\ActionData\ActionDataException;
use Akbarali\DataObject\DataObjectBase;
use Akbarali\NatsListener\Contracts\NatsCommandContract;
use Akbarali\NatsListener\Exceptions\InternalException;
use Akbarali\NatsListener\Exceptions\NatsException;
use Akbarali\NatsListener\Presenters\NatsApiResponse;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;

readonly class NatsChannelDispatcher
{
	public function __construct(
		protected mixed $auth,
		protected string $routeName,
		protected array $routes,
		protected array $params = []
	) {}
	
	protected function getAuthParam(): array
	{
		if (isset($this->auth)) {
			return ['auth' => $this->auth];
		}
		
		return [];
	}
	
	/**
	 * @throws ReflectionException
	 * @throws \JsonException
	 * @return NatsApiResponse
	 */
	public function call(): NatsApiResponse
	{
		try {
			$route   = $this->routes[$this->routeName] ?? throw NatsException::routeNotFound();
			$service = $route['service'][0] ?? throw NatsException::serviceNotFound();
			$method  = $route['service'][1] ?? throw NatsException::serviceMethodNotFound();
			
			if (!(new ReflectionClass($service))->implementsInterface(NatsCommandContract::class)) {
				throw NatsException::serviceInterfaceNotImplemented();
			}
			
			if (($route['auth'] ?? false) && empty($this->auth)) {
				throw NatsException::unauthenticatedRequest();
			}
			
			$instance         = app()->make($service, $this->getAuthParam());
			$reflection       = new ReflectionMethod($service, $method);
			$reflectionParams = $reflection->getParameters();
			
			if (count($reflectionParams) === 1) {
				$paramsType = $reflectionParams[0]->getType();
				if ($paramsType && !$paramsType->isBuiltin()) {
					$paramsClass = new ReflectionClass($paramsType->getName());
					if ($paramsClass->isSubclassOf(ActionDataBase::class) || $paramsClass->isSubclassOf(DataObjectBase::class)) {
						$value = $paramsType->getName()::fromArray($this->params);
						
						return new NatsApiResponse($instance->{$method}($value));
					}
				}
			}
			
			$params = $this->prepareParams($reflectionParams);
			
			return new NatsApiResponse($reflection->invokeArgs($instance, $params));
		} catch (Throwable $e) {
			Log::error($e);
			
			return $this->handleException($e);
		}
	}
	
	/**
	 * @throws NatsException
	 */
	private function prepareParams(array $reflectionParams): array
	{
		$params          = [];
		$paramsNotFilled = [];
		
		foreach ($reflectionParams as $reflectionParam) {
			$defaultValue                        = $reflectionParam->isDefaultValueAvailable() ? $reflectionParam->getDefaultValue() : null;
			$params[$reflectionParam->getName()] = $this->params[$reflectionParam->getName()] ?? $defaultValue;
			
			if (!$reflectionParam->isOptional() && is_null($params[$reflectionParam->getName()])) {
				$paramsNotFilled[] = "{$reflectionParam->getName()} is required";
			}
		}
		
		if (!empty($paramsNotFilled)) {
			throw NatsException::invalidParams($paramsNotFilled);
		}
		
		return $params;
	}
	
	private function handleException(Throwable $e): NatsApiResponse
	{
		return match (true) {
			$e instanceof BindingResolutionException,
				$e instanceof ReflectionException => NatsApiResponse::createNatsError(NatsException::reflectorError($e->getMessage())),
			$e instanceof ActionDataException     => NatsApiResponse::createNatsError(NatsException::actionDataError($e)),
			$e instanceof ValidationException     => NatsApiResponse::createNatsError(NatsException::validationError($e)),
			$e instanceof NatsException           => NatsApiResponse::createNatsError($e),
			$e instanceof InternalException       => NatsApiResponse::createInternalError($e),
			default                               => NatsApiResponse::createNatsError(NatsException::unknownError($e)),
		};
	}
}


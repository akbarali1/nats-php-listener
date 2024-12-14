<?php
declare(strict_types=1);

namespace Akbarali\NatsListener\Dispatchers;

use Akbarali\ActionData\ActionDataBase;
use Akbarali\ActionData\ActionDataException;
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
			$route                   = $this->routes[$this->routeName] ?? throw NatsException::routeNotFound();
			$service                 = $route['service'][0] ?? throw NatsException::serviceNotFound();
			$method                  = $route['service'][1] ?? throw NatsException::serviceMethodNotFound();
			$authenticationRequired  = $route['auth'] ?? false;
			$interfaceImplementation = (new ReflectionClass($service))->implementsInterface(NatsCommandContract::class);
			
			if (!$interfaceImplementation) {
				throw NatsException::serviceInterfaceNotImplemented();
			}
			
			if ($authenticationRequired && is_null($this->auth)) {
				throw NatsException::unauthenticatedRequest();
			}
			if (isset($route['parameterType'])) {
				$value = null;
				$class = new $route['parameterType'];
				if ($class instanceof ActionDataBase) {
					$value = $class::fromArray($this->params);
				}
				
				return new NatsApiResponse(app()->make($service, $this->getAuthParam())->{$method}($value));
			}
			
			$instance         = app()->make($service, $this->getAuthParam());
			$reflection       = new ReflectionMethod($service, $method);
			$reflectionParams = $reflection->getParameters();
			$params           = [];
			$paramsNotFilled  = [];
			foreach ($reflectionParams as $reflectionParam) {
				$defaultValue                        = $reflectionParam->isDefaultValueAvailable() ? $reflectionParam->getDefaultValue() : null;
				$params[$reflectionParam->getName()] = $this->params[$reflectionParam->getName()] ?? $defaultValue;
				if (!$reflectionParam->isOptional() && is_null($params[$reflectionParam->getName()])) {
					$paramsNotFilled[] = $reflectionParam->getName().' is required';
				}
			}
			
			if (count($paramsNotFilled) > 0) {
				throw NatsException::invalidParams($paramsNotFilled);
			}
			
			return new NatsApiResponse($reflection->invokeArgs($instance, $params));
		} catch (BindingResolutionException|ReflectionException $exception) {
			Log::error($exception);
			
			return NatsApiResponse::createNatsError(NatsException::reflectorError($exception->getMessage()));
		} catch (ActionDataException $e) {
			return NatsApiResponse::createNatsError(NatsException::actionDataError($e));
		} catch (ValidationException $e) {
			return NatsApiResponse::createNatsError(NatsException::validationError($e));
		} catch (NatsException $e) {
			Log::error($e);
			
			return NatsApiResponse::createNatsError($e);
		} catch (InternalException $e) {
			return NatsApiResponse::createInternalError($e);
		} catch (Throwable $e) {
			Log::error($e);
			
			return NatsApiResponse::createNatsError(NatsException::unknownError($e));
		}
	}
}


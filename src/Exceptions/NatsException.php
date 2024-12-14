<?php
declare(strict_types=1);

namespace Akbarali\NatsListener\Exceptions;

use Akbarali\ActionData\ActionDataException;
use Akbarali\NatsListener\Enums\ExceptionCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class NatsException extends InternalException
{
	
	public static function serviceNotFound(): static
	{
		return static::new(
			code: ExceptionCode::NATSServiceNotFound,
		);
	}
	
	public static function configNameNotSet(): static
	{
		return static::new(
			code: ExceptionCode::NATSConfigNameNotSet,
		);
	}
	
	public static function routeNotFound(): static
	{
		return static::new(
			code: ExceptionCode::NATSRouteNotFound,
		);
	}
	
	public static function invalidParams(array $string): static
	{
		return static::new(
			code       : ExceptionCode::NATSInvalidParams,
			description: json_encode($string),
		);
	}
	
	public static function reflectorError(string $getMessage): static
	{
		return static::new(
			code   : ExceptionCode::NATSReflectorError,
			message: $getMessage,
		);
	}
	
	public static function actionDataError(ActionDataException $e): static
	{
		return static::new(
			code       : ExceptionCode::NATSActionDataError,
			message    : $e->getMessage(),
			description: $e->getTraceAsString(),
		);
	}
	
	public static function unknownError(Throwable $exception): static
	{
		Log::error("Raw exception");
		Log::error($exception->getMessage());
		Log::error($exception->getTraceAsString());
		
		return static::new(
			code: ExceptionCode::NATSUnknownError,
		);
	}
	
	public static function serviceMethodNotFound(): static
	{
		return static::new(
			code: ExceptionCode::NATSServiceMethodNotFound,
		);
	}
	
	public static function requestMethodNotFound(): static
	{
		return static::new(
			code: ExceptionCode::NATSRequestMethodNotFound,
		);
	}
	
	public static function validationError(ValidationException $e): static
	{
		Log::error("Validation error", ['errors' => $e->errors()]);
		
		return static::new(
			code       : ExceptionCode::ValidationException,
			description: json_encode($e->errors(), JSON_THROW_ON_ERROR),
		);
	}
	
	public static function serviceInterfaceNotImplemented(): static
	{
		return static::new(
			code: ExceptionCode::NATSServiceInterfaceNotImplemented,
		);
	}
	
	public static function unauthenticatedRequest(): static
	{
		return static::new(
			code: ExceptionCode::NATSUnauthenticatedRequest,
		);
	}
	
	public static function routeFileNotFound(): static
	{
		return static::new(
			code: ExceptionCode::NATSRouteFileNotFound,
		);
	}
	
	public static function notSupportedFunction(): static
	{
		return static::new(
			code: ExceptionCode::NATSFunctionNotSupported,
		);
	}
	
	public static function responseError(string $message): static
	{
		return static::new(
			code   : ExceptionCode::NATSResponseError,
			message: $message,
		);
	}
	
	public static function noRoutes(): static
	{
		return static::new(
			code: ExceptionCode::NATSNoRoutes,
		);
	}
	
}
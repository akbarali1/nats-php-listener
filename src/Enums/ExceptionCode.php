<?php
declare(strict_types=1);

namespace Akbarali\NatsListener\Enums;

use Illuminate\Support\Facades\Route;

enum ExceptionCode: int
{
	case UnknownExceptionCode = -1000;
	
	#region NATS
	case NATSServiceNotFound                = -3000;
	case NATSRouteNotFound                  = -3001;
	case NATSInvalidParams                  = -3002;
	case NATSReflectorError                 = -3003;
	case NATSActionDataError                = -3004;
	case NATSUnknownError                   = -3005;
	case NATSServiceMethodNotFound          = -3006;
	case NATSRequestMethodNotFound          = -3007;
	case NATSServiceInterfaceNotImplemented = -3008;
	case NATSUnauthenticatedRequest         = -3009;
	case NATSRouteFileNotFound              = -3010;
	case NATSResponseError                  = -3011;
	case NATSConfigNameNotSet               = -3012;
	case NATSFunctionNotSupported           = -3013;
	case NATSNoRoutes                       = -3014;
	#endregion NATS
	
	case ValidationException = -4000;
	
	public function getStatusCode(): int
	{
		$value = $this->value;
		
		return match (true) {
			$value === -10000 => 404,
			$value === -20000 => 507,
			default           => 500,
		};
	}
	
	public function getMessage(): string
	{
		$key         = "exceptions.{$this->value}.message";
		$translation = trans($key);
		
		if ($key === $translation) {
			return "Something went wrong: ".$this->value;
		}
		
		return $translation;
	}
	
	public function getDescription(): string
	{
		$key         = "exceptions.{$this->value}.description";
		$translation = trans($key);
		
		if ($key === $translation) {
			return "No additional description provided: ".$this->value;
		}
		
		return $translation;
	}
	
	public static function getDescriptionByInternalCode(int $internalCode): string
	{
		$key         = "exceptions.{$internalCode}.description";
		$translation = trans($key);
		
		if ($key === $translation) {
			return "No additional description provided: ".$internalCode;
		}
		
		return $translation;
	}
	
	
	public function getDescriptionParams(array $params): string
	{
		$key         = "exceptions.{$this->value}.description";
		$translation = trans($key, $params);
		
		if ($key === $translation) {
			return "No additional description provided: ".$this->value;
		}
		
		return $translation;
	}
	
	public function getLink(): ?string
	{
		if (Route::has('docs.exceptions.code')) {
			return route('docs.exceptions.code', [
				'code' => $this->value,
			]);
		}
		
		return null;
	}
	
	public static function findExceptionCode(int $code): ExceptionCode
	{
		foreach (self::cases() as $value) {
			if ($value->value === $code) {
				return $value;
			}
		}
		
		return self::UnknownExceptionCode;
	}
	
	public static function count(): int
	{
		return count(self::cases());
	}
}
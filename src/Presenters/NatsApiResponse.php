<?php
declare(strict_types=1);

namespace Akbarali\NatsListener\Presenters;

use Akbarali\NatsListener\Exceptions\InternalException;
use Akbarali\NatsListener\Exceptions\NatsException;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class NatsApiResponse implements Jsonable, JsonSerializable
{
	public bool    $success      = true;
	public ?string $errorMessage = null;
	
	public function __construct(
		protected mixed $_data = null,
		public ?int $errorCode = null,
		public InternalException|null $exception = null
	) {}
	
	public static function createErrorResponse(int $errorCode, string $errorMessage): static
	{
		$self          = new static($errorMessage, $errorCode);
		$self->success = false;
		
		return $self;
	}
	
	public static function createNatsError(NatsException $e): static
	{
		return (new static())->setError($e);
	}
	
	public function setError(InternalException $e): static
	{
		$this->success   = false;
		$this->exception = $e;
		
		return $this;
	}
	
	public static function createInternalError(InternalException $e): static
	{
		return (new static())->setError($e);
	}
	
	/**
	 * @throws \JsonException
	 */
	public function toJson($options = 0): string
	{
		return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR);
	}
	
	public function toArray(): array
	{
		return $this->jsonSerialize();
	}
	
	public function jsonSerialize(): array
	{
		if ($this->success) {
			return [
				"success" => $this->success,
				"data"    => $this->_data,
			];
		}
		
		if (isset($this->exception)) {
			return [
				"success" => $this->success,
				"error"   => [
					"code"        => $this->exception->getInternalCode()->value,
					"message"     => $this->exception->getMessage(),
					"description" => $this->exception->getDescription(),
					"link"        => $this->exception->getInternalCode()->getLink(),
				],
			];
		}
		
		return [
			"success" => $this->success,
			"error"   => [
				"code"    => $this->errorCode,
				"message" => $this->errorMessage,
			],
		];
	}
}

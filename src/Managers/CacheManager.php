<?php
declare(strict_types=1);

namespace Akbarali\NatsListener\Managers;

use Illuminate\Support\Facades\Cache;

class CacheManager
{
	
	public function setCache(int $pid): void
	{
		$runningId = [$pid];
		if (Cache::has('nats:channel:config')) {
			$processIds = $this->getProcessIds();
			if (!empty($processIds)) {
				$processIds = array_merge($processIds, $runningId);
			}
		} else {
			$processIds = $runningId;
		}
		
		Cache::put('nats:channel:config', [
			'pids' => $processIds,
		]);
	}
	
	public function forgetCache(int $runningId): void
	{
		$processIds = $this->getProcessIds();
		if (count($processIds) === 0) {
			return;
		}
		
		if (count($processIds) === 1) {
			Cache::forget('nats:channel:config');
			
			return;
		}
		
		$this->changeProcessId($runningId);
	}
	
	
	public function getConfig(): array
	{
		return Cache::get('nats:channel:config', []);
	}
	
	public function getProcessIds(): array
	{
		return $this->getConfig()['pids'] ?? [];
	}
	
	public function changeProcessId(int $processId): void
	{
		Cache::put('nats:channel:config', [
			'pids' => array_filter($this->getProcessIds(), static fn($id): bool => $id !== $processId),
		]);
	}
	
}
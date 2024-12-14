<?php

namespace Akbarali\NatsListener\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TerminateCommand extends Command
{
	
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'nats:terminate';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Terminate the master supervisor so it can be restarted';
	
	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function handle(): void
	{
		$config = Cache::get('nats:channel:config');
		if (!$config) {
			$this->components->error('Config not found');
			
			return;
		}
		$processIds = $config['pids'] ?? null;
		if (is_null($processIds)) {
			$this->components->error('PID not found');
			
			return;
		}
		
		if (count($processIds) === 0) {
			$this->components->error("Running processes not found");
			
			return;
		}
		
		foreach ($processIds as $processId) {
			$result = true;
			$this->components->task("Process: $processId", function () use ($processId, &$result) {
				return $result = posix_kill($processId, SIGTERM);
			});
			
			if (!$result) {
				$this->components->error("Failed to kill process: {$processId} (".posix_strerror(posix_get_last_error()).')');
			}
		}
	}
}

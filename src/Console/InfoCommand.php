<?php

namespace Akbarali\NatsListener\Console;

use Akbarali\NatsListener\Managers\CacheManager;
use Illuminate\Console\Command;

class InfoCommand extends Command
{
	
	public function __construct(
		protected CacheManager $cacheManager
	) {
		parent::__construct();
	}
	
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'nats:info';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Terminate the master supervisor so it can be pause';
	
	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function handle(): void
	{
		$processIds = $this->cacheManager->getProcessIds();
		
		if (count($processIds) === 0) {
			$this->components->error("Running processes not found");
			
			return;
		}
		
		foreach ($processIds as $processId) {
		
		}
	}
}

<?php


namespace Imageplus\APIVersionControl\Console\Commands;


use Illuminate\Console\Command;
use Imageplus\APIVersionControl\Classes\Device;
use Imageplus\APIVersionControl\Facades\APIVersionControl;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HasAccessCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'api:has-access {--device=} {--device_ver=} {--feature=} {--api_ver=}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'API VCS Test api access for a device. Add --feature to test a specific feature';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		if (!$this->option('device') || !$this->option('device_ver')){
			$this->error('You need to specify a valid device and version');
			return;
		}

		if ($this->option('api_ver')){
			try {
				APIVersionControl::registerRequestGlobalVersion($this->option('api_ver'));
			}
			catch (NotFoundHttpException $exception){
				$this->error('API Version doesn\'t exist');
				return;
			}
			catch (\Exception $exception){
				$this->error('API Version is blocked');
				return;
			}
		}

		try {
			$device = APIVersionControl::registerRequestDevice($this->option('device'), $this->option('device_ver'));
		}
		catch (HttpException $exception){
			$this->error($exception->getMessage());
			return;
		}

		if (!APIVersionControl::hasAccess($this->option('feature'))) {
			$this->error(APIVersionControl::getAccessMessage($this->option('feature')));
			return;
		}

		$this->info('The device should have access');
	}
}
<?php

namespace Mckue\Excel;

use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use Lysice\XlsWriter\Commands\InfoCommand;
use Mckue\Excel\Files\Filesystem;
use Mckue\Excel\Files\TemporaryFileFactory;
use Mckue\Excel\Transactions\TransactionHandler;
use Mckue\Excel\Transactions\TransactionManager;

class ExcelServiceProvider extends ServiceProvider
{
	protected array $commands = [
		InfoCommand::class,
	];
    /**
     * {@inheritdoc}
     */
    public function boot(): void
	{
        if ($this->app->runningInConsole()) {
            if ($this->app instanceof LumenApplication) {
                $this->app->configure('excel');
            } else {
                $this->publishes([
                    $this->getConfigFile() => config_path('mckue-excel.php'),
                ], 'config');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
	{
        $this->mergeConfigFrom(
            $this->getConfigFile(),
            'mckue-excel'
        );

		$this->app->singleton(TransactionManager::class, function ($app) {
			return new TransactionManager($app);
		});

		$this->app->bind(TransactionHandler::class, function ($app) {
			return $app->make(TransactionManager::class)->driver();
		});

        $this->app->bind(TemporaryFileFactory::class, function () {
            return new TemporaryFileFactory(
                config('mckue-excel.temporary_files.local_path', config('mckue-excel.exports.temp_path', storage_path('framework/laravel-excel'))),
                config('mckue-excel.temporary_files.remote_disk')
            );
        });

        $this->app->bind(Filesystem::class, function ($app) {
            return new Filesystem($app->make('filesystem'));
        });

        $this->app->bind('excel', function ($app) {
            return new Excel(
                $app->make(Writer::class),
                $app->make(Reader::class),
                $app->make(Filesystem::class)
            );
        });

        $this->app->alias('excel', Excel::class);
        $this->app->alias('excel', Exporter::class);
        $this->app->alias('excel', Importer::class);

		$this->registerCommands();
    }

    /**
     * @return string
     */
    protected function getConfigFile(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'mckue-excel.php';
    }


	/**
	 * register the commands
	 */
	private function registerCommands(): void
	{
		$this->commands($this->commands);
	}
}

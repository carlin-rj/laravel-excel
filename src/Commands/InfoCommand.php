<?php

namespace Mckue\Excel\Commands;

use Illuminate\Console\Command;

class InfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'php-ext-xlswriter:status';

    /**
     * @var string
     */
    protected string $version = '1.0';

    /**
     * @var string
     */
    protected string $docsUrl = 'https://github.com/mckue/laravel-excel';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'show status for laravel-excel';

	/**
     * Execute the console command.
     *
     */
    public function handle(): void
	{
        $result = [
            'loaded' =>  extension_loaded('xlswriter') ? 'yes' : 'no',
            'xlsWriter author' => function_exists('xlswriter_get_author') ? xlswriter_get_author() : '',
        ];
        $execInfo = shell_exec('php --ri xlswriter');
        $execInfo =  explode(PHP_EOL, $execInfo);
        foreach ($execInfo as $index => $item) {
            if (empty($item) or strpos($item, '=>') == false) {
                unset($execInfo[$index]);
            }
        }
        foreach ($execInfo as $value) {
            $arr = explode('=>', $value);
            $result[trim($arr[0])] = trim($arr[1]);
        }

        $data = [
            'version' => $this->version,
            'author' => 'mckue<https://github.com/mckue>',
            'docs' => $this->docsUrl
        ];
        $this->displayTables('laravel-excel info:', $data);
        $this->displayTables('XlsWriter extension status:', $result);
    }


    protected function displayTables(string $title, $data): void
	{
        $this->line($title);
        $this->table([], $this->parseTable($data));
    }

    /**
     * Make up the table for console display.
     *
     * @param $input
     *
     * @return array
     */
    protected function parseTable(array $input): array
	{
        return array_map(static function ($key, $value) {
            return [
                'key'       => $key,
                'value'     => $value,
            ];
        }, array_keys($input), $input);
    }
}

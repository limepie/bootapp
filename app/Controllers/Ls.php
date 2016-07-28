<?php
namespace App\Controllers;

class Ls extends Command
{
    use \App\Traits\Machine;
    use \App\Traits\Docker\Ls;
    /**
     * @var string
     */
    public $command = 'ls';

    /**
     * @param \Peanut\Console\Application $app
     */
    public function configuration(\Peanut\Console\Application $app)
    {
        $app->option('all', ['require' => false, 'alias' => 'a', 'value' => false]);
    }

    /**
     * @param \Peanut\Console\Application $app
     * @param array                       $config
     */
    public function exec(\Peanut\Console\Application $app, array $config)
    {
        $isAll  = $app->getOption('all');
        $config = $this->getConfig();
        $this->initMachine($config);
        echo PHP_EOL;
        $this->dockerLs($isAll);
    }
}

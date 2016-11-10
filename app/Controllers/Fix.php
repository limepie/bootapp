<?php
namespace App\Controllers;

class Fix extends Command
{
    use \App\Traits\Machine;

    /**
     * @var string
     */
    public $command = 'fix';

    /**
     * @param \Peanut\Console\Application $app
     */
    public function configuration(\Peanut\Console\Application $app)
    {
        $app->argument('target');
    }

    /**
     * @param \Peanut\Console\Application $app
     * @param array                       $config
     */
    public function exec(\Peanut\Console\Application $app, array $config)
    {
        $this->initMachine();
    }
}

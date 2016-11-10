<?php
namespace App\Controllers;

class Halt extends Command
{
    use \App\Traits\Machine;

    /**
     * @var string
     */
    public $command = 'halt';

    /**
     * @param \Peanut\Console\Application $app
     */
    public function configuration(\Peanut\Console\Application $app)
    {
    }

    /**
     * @param \Peanut\Console\Application $app
     * @param array                       $config
     */
    public function exec(\Peanut\Console\Application $app, array $config)
    {
        $this->machineHalt();
    }
}

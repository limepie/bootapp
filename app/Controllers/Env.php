<?php

namespace App\Controllers;

class Env extends Command
{
    use \App\Traits\Machine;

    /**
     * @var string
     */
    public $command = 'env';

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
        $command = [
            'docker-machine',
            'env',
            $this->getMachineName()
        ];
        echo $this->process($command, ['print' => false]);
    }
}

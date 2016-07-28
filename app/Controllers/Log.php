<?php
namespace App\Controllers;

class Log extends Command
{
    use \App\Traits\Machine;
    /**
     * @var string
     */
    public $command = 'log';

    /**
     * @param \Peanut\Console\Application $app
     */
    public function configuration(\Peanut\Console\Application $app)
    {
        $app->argument('container name');
    }

    /**
     * @param \Peanut\Console\Application $app
     * @param array                       $config
     */
    public function exec(\Peanut\Console\Application $app, array $config)
    {
        $name = $app->getArgument('container name');
        $this->initMachine();
        echo PHP_EOL;
        $this->dockerLog($name);
    }

    /**
     * @param $name
     */
    public function dockerLog($name)
    {
        $containerName = $this->getContainerName($name);

        $command = [
            'docker',
            'logs',
            $containerName
        ];

        echo $this->process($command, ['print' => false]);
    }
}

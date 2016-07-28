<?php
namespace App\Controllers;

class Ssh extends Command
{
    use \App\Traits\Machine;
    /**
     * @var string
     */
    public $command = 'ssh';

    /**
     * @param \Peanut\Console\Application $app
     */
    public function configuration(\Peanut\Console\Application $app)
    {
        $app->argument('container name');
        $app->argument('command', false);
        $app->option('force', ['require' => false, 'alias' => 'f', 'value' => false]);
    }

    /**
     * @param \Peanut\Console\Application $app
     * @param array                       $config
     */
    public function exec(\Peanut\Console\Application $app, array $config)
    {
        $name    = $app->getArgument('container name');
        $command = $app->getArgument('command');

        $config = $this->getConfig();
        $this->initMachine($config);
        echo PHP_EOL;

        $this->dockerSsh($name, $command);
    }

    /**
     * @param $name
     * @param $cmd
     */
    public function dockerSsh($name, $cmd = '')
    {
        $containerName = $this->getContainerName($name);

        if (!$cmd) {
            $cmd = '/bin/bash';
        }

        $command = [
            'docker',
            'exec',
            '-it',
            $containerName,
            $cmd
        ];
//        $command[] = '> `tty`';

        $this->process($command, ['tty' => true]);
    }
}

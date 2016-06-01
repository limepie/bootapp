<?php
namespace Bootapp\Docker;

use Symfony\Component\Console\Command\Command;

class Ssh extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('ssh')
            ->setDescription('Log into or run a command on a machine with SSH');
    }

    protected function process()
    {
        //config
        {
            $this->message('Config', 'title');
            $stageName   = 'local'; //$this->input->getArgument('enviroment');
            $config      = parent::getConfigFile($stageName);
            $machineName = $config['machine'];
            $projectName = $config['project'];
        }

        system('docker-machine ssh '.$machineName.' > `tty`'); //$this->command('docker-machine ssh '.$machineName');
    }
}

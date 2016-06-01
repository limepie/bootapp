<?php
namespace Bootapp\Docker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Down extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('down')
            ->setDescription('Stop and remove containers, networks, images, and volumes');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function process()
    {
        // config
        {
            $this->message('Config', 'title');
            $stageName   = 'local'; //$this->input->getArgument('enviroment');
            $config      = parent::getConfigFile($stageName);
            $machineName = $config['machine'];
            $projectName = $config['project'];
        }

        // docker
        {
            $this->initDockerMachine($machineName);
        }

        $whichDockerCompose = $this->whichDockerCompose();
        $messages           = $this->command($whichDockerCompose.' -p '.$machineName.' down --remove-orphans');
        $this->writeln($messages, OutputInterface::VERBOSITY_QUIET);

    }
}

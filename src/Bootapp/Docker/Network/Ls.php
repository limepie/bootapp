<?php
namespace Bootapp\Docker\Network;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Ls extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('docker-network:ls')
            ->setDescription('List all networks');
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

        {
            $this->writeln('');
            $this->message('Run', 'title');

            $whichDocker = $this->whichDocker();
            $messages    = $this->command($whichDocker.' network ls');
            $this->writeln($messages, OutputInterface::VERBOSITY_QUIET);
        }
    }
}

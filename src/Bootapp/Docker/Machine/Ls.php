<?php
namespace Bootapp\Docker\Machine;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Ls extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('docker-machine:ls')
            ->setDescription('List machines');
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

        $whichDockerMachine = $this->whichDockerMachine();
        $messages           = $this->command($whichDockerMachine.' ls');
        $this->writeln($messages, OutputInterface::VERBOSITY_QUIET);
    }
}

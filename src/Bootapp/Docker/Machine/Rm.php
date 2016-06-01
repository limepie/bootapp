<?php
namespace Bootapp\Docker\Machine;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Rm extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('docker-machine:rm')
            ->setDescription('Remove a machine');
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
        $messages           = $this->command($whichDockerMachine.' rm '.$machineName, 'y');

        $subnetFile = $this->command('echo $HOME')->toString().'/.docker/docker-machine-subnet.yaml';
        $subnets    = $this->yamlParseFile($subnetFile);

        if (true === is_array($subnets) && true === isset($subnets[$machineName])) {
            unset($subnets[$machineName]);
        }

        $this->yamlDumpFile($subnetFile, $subnets);

        $this->writeln($messages, OutputInterface::VERBOSITY_QUIET);
    }
}

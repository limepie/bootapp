<?php
namespace Bootapp\Docker\Compose;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Scale extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('docker-compose:scale')
            ->setDescription('Set number of containers for a service')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'require name'
            );
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
            $name        = $this->input->getArgument('name');
        }

        // docker
        {
            $this->initDockerMachine($machineName);
        }

        {
            $this->writeln('');
            $this->message('Run', 'title');

            $whichDockerCompose = $this->whichDockerCompose();

            $messages = $this->command($whichDockerCompose.' -p '.$machineName.' scale '.$name);
            $this->writeln($messages, OutputInterface::VERBOSITY_QUIET);
        }
    }
}

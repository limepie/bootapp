<?php
namespace Bootapp\Docker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Scale extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('scale')
            ->setDescription('Return low-level information on a container or image')
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

<?php
namespace Bootapp\Docker\Network;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Rm extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('network:rm')
            ->setDescription('Remove a network')
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

            $whichDocker = $this->whichDocker();
            /*
            $network     = $this->command($whichDocker.' network ls | grep default')->toArray();

            foreach ($network as $row) {
                preg_match('#(?P<pid>[^\s]+)\s+(?P<name>[^\s]+)\s+(?P<type>[^\s]+)#', $row, $match);
                $messages = $this->command($whichDocker.' network rm '.$match['pid']);
                $this->writeln($messages, OutputInterface::VERBOSITY_NORMAL);
            }
            */
            $messages = $this->command($whichDocker.' network rm '.$name);
            $this->writeln($messages, OutputInterface::VERBOSITY_QUIET);
        }
    }
}

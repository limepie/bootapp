<?php
namespace Bootapp\Docker\Machine;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Inspect extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('machine:inspect')
            ->setDescription('Inspect information about a machine')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'require name'
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED,
                'Format the output using the given go template'
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

        $formatString = '';
        $format       = $this->input->getOption('format');

        if ($format) {
            if (false !== strpos($format, '\"')) {
                $formatString = ' --format="'.$format.'"';
            } elseif (false !== strpos($format, "\'")) {
                $formatString = " --format='".$format."'";
            } elseif (false !== strpos($format, "'")) {
                $formatString = ' --format="'.$format.'"';
            } else {
                $formatString = " --format='".$format."'";
            }
        }

        $whichDockerMachine = $this->whichDockerMachine();
        $messages           = $this->command($whichDockerMachine.' inspect '.$formatString.$name);
        $this->writeln($messages, OutputInterface::VERBOSITY_QUIET);
    }
}

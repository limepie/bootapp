<?php
namespace Bootapp\Docker\Composer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

class Install extends \Bootapp\Command
{
    protected function configure()
    {
        $this
            ->setName('composer:install')
            ->setDescription('Installs the project dependencies from composer.json');
    }

    public function process()
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

        // compose install
        {
            $this->writeln('');
            $this->writeln('<info>Php composer install</info>');
            $whichDocker = $this->whichDocker();

            $container = $this->command($whichDocker.' ps -f name='.$projectName.'_php -q')->toString();

            if ($container) {
                $message = $this->command('docker exec -i $(docker ps -f name='.$projectName.'_php -q) sh -c  "cd /var/www/ && composer install --prefer-dist -vvv --profile"');

                $this->writeln('Ok', OutputInterface::VERBOSITY_QUIET);
            } else {
                $this->writeln('not found php container', OutputInterface::VERBOSITY_QUIET);
            }
        }
    }
}

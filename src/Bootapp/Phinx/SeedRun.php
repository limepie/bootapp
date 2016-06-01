<?php
namespace Bootapp\Phinx;

use Bootapp\Phinx;
use Symfony\Component\Console\Input\InputOption;

class SeedRun extends \Phinx\Console\Command\SeedRun
{
    use Phinx;
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment'$this->defaultEnvironment);

        $this->setName('phinx:seed:run')
            ->setDescription('Run database seeders')
            ->addOption('--seed', '-s', InputOption::VALUE_REQUIRED, 'What is the name of the seeder?')
            ->setHelp(
                <<<EOT
The <info>seed:run</info> command runs all available or individual seeders

<info>bootapp phinx:seed:run -e development</info>
<info>phinx seed:run -e development -s UserSeeder</info>
<info>phinx seed:run -e development -v</info>

EOT
            );
    }
}

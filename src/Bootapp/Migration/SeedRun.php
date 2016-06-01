<?php
namespace Bootapp\Migration;

use Bootapp\Migration;
use Symfony\Component\Console\Input\InputOption;

class SeedRun extends \Phinx\Console\Command\SeedRun
{
    use Migration;
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment'$this->defaultEnvironment);

        $this->setName('migration:seed:run')
            ->setDescription('Run database seeders')
            ->addOption('--seed', '-s', InputOption::VALUE_REQUIRED, 'What is the name of the seeder?')
            ->setHelp(
                <<<EOT
The <info>seed:run</info> command runs all available or individual seeders

<info>bootapp migration:seed:run -e development</info>
<info>bootapp migration:seed:run -e development -s UserSeeder</info>
<info>bootapp migration:seed:run -e development -v</info>

EOT
            );
    }
}

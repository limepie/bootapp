<?php
namespace Bootapp\Migration;

use Bootapp\Migration;
use Symfony\Component\Console\Input\InputOption;

class Migrate extends \Phinx\Console\Command\Migrate
{
    use Migration;
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment', $this->defaultEnvironment);

        $this->setName('migration:migrate')
            ->setDescription('Migrate the database')
            ->addOption('--target', '-t', InputOption::VALUE_REQUIRED, 'The version number to migrate to')
            ->addOption('--date', '-d', InputOption::VALUE_REQUIRED, 'The date to migrate to')
            ->setHelp(
                <<<EOT
The <info>migrate</info> command runs all available migrations, optionally up to a specific version

<info>bootapp migration:migrate -e development</info>
<info>bootapp migration:migrate -e development -t 20110103081132</info>
<info>bootapp migration:migrate -e development -d 20110103</info>
<info>bootapp migration:migrate -e development -v</info>

EOT
            );
    }
}

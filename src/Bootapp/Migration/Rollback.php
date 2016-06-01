<?php
namespace Bootapp\Migration;

use Bootapp\Migration;
use Symfony\Component\Console\Input\InputOption;

class Rollback extends \Phinx\Console\Command\Rollback
{
    use Migration;
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment', $this->defaultEnvironment);

        $this->setName('migration:rollback')
            ->setDescription('Rollback the last or to a specific migration')
            ->addOption('--target', '-t', InputOption::VALUE_REQUIRED, 'The version number to rollback to')
            ->addOption('--date', '-d', InputOption::VALUE_REQUIRED, 'The date to rollback to')
            ->setHelp(
                <<<EOT
The <info>rollback</info> command reverts the last migration, or optionally up to a specific version

<info>bootapp migration:rollback -e development</info>
<info>bootapp migration:rollback -e development -t 20111018185412</info>
<info>bootapp migration:rollback -e development -d 20111018</info>
<info>bootapp migration:rollback -e development -v</info>

EOT
            );
    }
}

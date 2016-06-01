<?php
namespace Bootapp\Migration;

use Bootapp\Migration;
use Symfony\Component\Console\Input\InputOption;

class Status extends \Phinx\Console\Command\Status
{
    use Migration;
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment.', $this->defaultEnvironment);

        $this->setName('migration:status')
            ->setDescription('Show migration status')
            ->addOption('--format', '-f', InputOption::VALUE_REQUIRED, 'The output format: text or json. Defaults to text.')
            ->setHelp(
                <<<EOT
The <info>status</info> command prints a list of all migrations, along with their current status

<info>bootapp migration:status -e development</info>
<info>bootapp migration:status -e development -f json</info>
EOT
            );
    }
}

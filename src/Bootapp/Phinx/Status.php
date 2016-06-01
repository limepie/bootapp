<?php
namespace Bootapp\Phinx;

use Bootapp\Phinx;
use Symfony\Component\Console\Input\InputOption;

class Status extends \Phinx\Console\Command\Status
{
    use Phinx;
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment.', $this->defaultEnvironment);

        $this->setName('phinx:status')
            ->setDescription('Show migration status')
            ->addOption('--format', '-f', InputOption::VALUE_REQUIRED, 'The output format: text or json. Defaults to text.')
            ->setHelp(
                <<<EOT
The <info>status</info> command prints a list of all migrations, along with their current status

<info>bootapp phinx:status -e development</info>
<info>bootapp phinx:status -e development -f json</info>
EOT
            );
    }
}

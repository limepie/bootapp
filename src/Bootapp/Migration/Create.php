<?php
namespace Bootapp\Migration;

use Bootapp\Migration;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Create extends \Phinx\Console\Command\Create
{
    use Migration;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('migration:create')
            ->setDescription('Create a new migration')
            ->addArgument('name', InputArgument::REQUIRED, 'What is the name of the migration?')
            ->setHelp(sprintf(
                '%sCreates a new database migration%s',
                PHP_EOL,
                PHP_EOL
            ));

        // An alternative template.
        $this->addOption('template', 't', InputOption::VALUE_REQUIRED, 'Use an alternative template');

        // A classname to be used to gain access to the template content as well as the ability to
        // have a callback once the migration file has been created.
        $this->addOption('class', 'l', InputOption::VALUE_REQUIRED, 'Use a class implementing "'.self::CREATION_INTERFACE.'" to generate the template');

    }
}

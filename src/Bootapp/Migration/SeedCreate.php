<?php
namespace Bootapp\Migration;

use Bootapp\Migration;
use Symfony\Component\Console\Input\InputArgument;

class SeedCreate extends \Phinx\Console\Command\SeedCreate
{
    use Migration;
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('migration:seed:create')
            ->setDescription('Create a new database seeder')
            ->addArgument('name', InputArgument::REQUIRED, 'What is the name of the seeder?')
            ->setHelp(sprintf(
                '%sCreates a new database seeder%s',
                PHP_EOL,
                PHP_EOL
            ));
    }
}

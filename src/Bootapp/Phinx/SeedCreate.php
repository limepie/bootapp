<?php
namespace Bootapp\Phinx;

use Bootapp\Phinx;
use Symfony\Component\Console\Input\InputArgument;

class SeedCreate extends \Phinx\Console\Command\SeedCreate
{
    use Phinx;
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('phinx:seed:create')
            ->setDescription('Create a new database seeder')
            ->addArgument('name', InputArgument::REQUIRED, 'What is the name of the seeder?')
            ->setHelp(sprintf(
                '%sCreates a new database seeder%s',
                PHP_EOL,
                PHP_EOL
            ));
    }
}

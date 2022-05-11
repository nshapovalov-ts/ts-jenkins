<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\CategoryTree\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateTree extends Command
{

    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $name = $input->getArgument(self::NAME_ARGUMENT);
        $option = $input->getOption(self::NAME_OPTION);
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $categoryTree = $objectManager->create('Vdcstore\CategoryTree\Model\CategoryTree');
            $categoryTree->updateCategoryTree();
            $output->writeln("Categories updated successfully.");
        } catch (\Exception $ex) {
            $output->writeln("Error during update " . $ex->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("catalog:category:updatetree");
        $this->setDescription("Update Category tree. Hide categories from menu section");
        $this->setDefinition([
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
        ]);
        parent::configure();
    }
}


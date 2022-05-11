<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\Performance\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManagerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;

class CachingTopmenu extends Command
{
    const IS_CACHE_DELETE = "is_cache_delete";

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ObjectManagerFactory
     */
    private $objectManagerFactory;

    public function __construct(
        ObjectManagerFactory $objectManagerFactory,
        $name = null
    ) {
        $this->objectManagerFactory = $objectManagerFactory;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->objectManager = $this->objectManagerFactory->create($_SERVER);
        $appState = $this->objectManager->get(State::class);
        $appState->setAreaCode(Area::AREA_FRONTEND);
        $configLoader = $this->objectManager->get(\Magento\Framework\ObjectManager\ConfigLoaderInterface::class);
        $this->objectManager->configure($configLoader->load('frontend'));
        $this->objectManager->get(\Magento\Framework\View\DesignInterface::class)->setDesignTheme('Sm/market_child', 'frontend');
        $this->objectManager->get(\Vdcstore\CategoryTree\Model\CategoryTree::class)->updateCategoryTree();

        $output->writeln("Include in Menu updated for menu category");

        $layout = $this->objectManager->get(\Magento\Framework\View\LayoutInterface::class);
        $block = $layout->createBlock(\Sm\MegaMenu\Block\MegaMenu\View::class);
        $block->setTemplate("Sm_MegaMenu::category-megamenu.phtml");
        if ($isCacheDelete = $input->getOption(self::IS_CACHE_DELETE)) {
            if ($isCacheDelete == 1) {
                $block->setIsCacheDelete(true);
            }
        }
        $block->toHtml();
        $output->writeln("Megamenu cache generated");
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::IS_CACHE_DELETE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Name'
            )
        ];
        $this->setName("sm_megamenu:caching");
        $this->setDescription("Caching Megamenu")
            ->setDefinition($options);

        parent::configure();
    }
}


<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Observer;

use Amasty\Faq\Model\ConfigProvider;
use Amasty\Faq\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\UrlInterface;

class CatalogMenuObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var nodeFactory
     */
    private $nodeFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        ConfigProvider $configProvider,
        NodeFactory $nodeFactory,
        CollectionFactory $collectionFactory,
        UrlInterface $urlBuilder
    ) {
        $this->configProvider = $configProvider;
        $this->nodeFactory = $nodeFactory;
        $this->collectionFactory = $collectionFactory->create();
        $this->urlBuilder = $urlBuilder;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->configProvider->isAddToMainMenu() || !$this->configProvider->isEnabled()) {
            return;
        }

        /** @var \Magento\Framework\Data\Tree\Node $menu */
        $menu = $observer->getMenu();
        if ($this->configProvider->isUseFaqCmsHomePage()) {
            $url = $this->urlBuilder->getUrl($this->configProvider->getUrlKey());
        } else {
            $url = $this->urlBuilder->getUrl($this->collectionFactory->getFirstCategoryUrl());
        }

        $node = $this->nodeFactory->create(
            [
                'data' => [
                    'name'   => $this->configProvider->getLabel(),
                    'id'     => 'amfaq-category-link',
                    'url'    => $url
                ],
                'idField' => 'amfaq-category-link',
                'tree' => $menu->getTree(),
                'parent' => $menu
            ]
        );
        $menu->addChild($node);
    }
}

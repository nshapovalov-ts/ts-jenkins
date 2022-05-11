<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\ExtendedMirakl\Rewrite\Mirakl\Catalog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Catalog\Model\Category as CategoryObject;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory as CategoryTreeFactory;
use Magento\Catalog\Model\ResourceModel\Category\Tree as CategoryTree;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\CategoryFactory as CategoryResourceFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\Api\Helper\Category as Api;
use Mirakl\Connector\Common\ExportInterface;
use Mirakl\Connector\Common\ExportTrait;
use Mirakl\Connector\Helper\Config;
use Mirakl\Process\Model\Process;
use Mirakl\Mci\Helper\Config as MciConfigHelper;

class Category extends \Mirakl\Catalog\Helper\Category
{
	/**
     * @param   Context                     $context
     * @param   StoreManagerInterface       $storeManager
     * @param   Api                         $api
     * @param   Config                      $config
     * @param   CategoryTreeFactory         $categoryTreeFactory
     * @param   CategoryFactory             $categoryFactory
     * @param   CategoryResourceFactory     $categoryResourceFactory
     * @param   CategoryCollectionFactory   $categoryCollectionFactory
     * @param   EntityManager               $entityManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Api $api,
        Config $config,
        CategoryTreeFactory $categoryTreeFactory,
        CategoryFactory $categoryFactory,
        CategoryResourceFactory $categoryResourceFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        EntityManager $entityManager,
        MciConfigHelper $mciConfigHelper
    ) {
        parent::__construct($context, $storeManager, $api, $config, $categoryTreeFactory, $categoryFactory, $categoryResourceFactory, $categoryCollectionFactory, $entityManager);
        $this->storeManager              = $storeManager;
        $this->api                       = $api;
        $this->config                    = $config;
        $this->categoryTreeFactory       = $categoryTreeFactory;
        $this->categoryFactory           = $categoryFactory;
        $this->categoryResourceFactory   = $categoryResourceFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->entityManager             = $entityManager;
        $this->mciConfigHelper           = $mciConfigHelper;
    }
	/**
     * @param   CategoryObject  $category
     * @param   mixed           $store
     * @return  CategoryTree
     */
    public function getTree(CategoryObject $category = null, $store = null)
    {
        if (null === $store) {
            $storeId = $this->config->getCatalogIntegrationStore()->getId();
        } else {
            $storeId = $this->storeManager->getStore($store)->getId();
        }

        if (!isset($this->treeCache[$storeId])) {
            /** @var CategoryTree $tree */
            $tree = $this->categoryTreeFactory->create();

            if (null === $category) {
                // $rootId = $this->getDefaultStore()->getRootCategoryId();
                // Get Selected Mirakl root category
                $rootId = $this->mciConfigHelper->getHierarchyRootCategoryId();
                /** @var CategoryObject $category */
                $category = $this->categoryFactory->create();
                $this->entityManager->load($category, $rootId);
            }

            $tree->loadNode($category->getId())->loadChildren();

            $collection = $this->_getDefaultCollection();
            $collection->setStoreId($storeId);

            $tree->addCollectionData($collection);

            $this->treeCache[$storeId] = $tree;
        }

        return $this->treeCache[$storeId];
    }
}


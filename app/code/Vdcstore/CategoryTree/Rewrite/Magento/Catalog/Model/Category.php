<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\CategoryTree\Rewrite\Magento\Catalog\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\Catalog\Model\ResourceModel;

class Category extends \Magento\Catalog\Model\Layer\Category
{
	/**
     * @param ContextInterface $context
     * @param StateFactory $layerStateFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $catalogProduct
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Registry $registry
     * @param CategoryRepositoryInterface $categoryRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\ContextInterface $context,
        \Magento\Catalog\Model\Layer\StateFactory $layerStateFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product $catalogProduct,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Vdcstore\CategoryTree\Helper\Data $helper,
        array $data = []
    ) {
    	$this->_productCollectionFactory = $productCollectionFactory;
        $this->helper = $helper;
        parent::__construct(
            $context,
            $layerStateFactory,
            $attributeCollectionFactory,
            $catalogProduct,
            $storeManager,
            $registry,
            $categoryRepository,
            $data
        );
    }

    /**
     * Retrieve current layer product collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection()
    {
        if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
        } else {
            $collection = $this->collectionProvider->getCollection($this->getCurrentCategory());
            //$this->prepareProductCollection($collection);
            $origCategory = $this->getOrigCategory();
            if($origCategory->getChildCategories()) {
                $collection->addCategoriesFilter(array('in' => explode(",", $origCategory->getChildCategories())));
            } else {
                $collection->addCategoriesFilter(array('in' => [$origCategory->getMappedCategory()]));
            }
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }
        return $collection;
    }

    public function getCurrentCategory()
    {
        $menuRootCategory = $this->helper->getMenuRoot();
        $category = $this->categoryRepository->get($menuRootCategory);
        $this->setData('current_category', $category);
        // $this->registry->unregister('current_category');
        // $this->registry->register('current_category', $category);
        return $category;
    }

    /**
     * Retrieve current category model
     * If no category found in registry, the root will be taken
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getOrigCategory()
    {
        $category = null;
        if ($category === null) {
            $category = $this->registry->registry('current_category');
            if ($category) {
                //$this->setData('current_category', $category);
            } else {
                $category = $this->categoryRepository->get($this->getCurrentStore()->getRootCategoryId());
                //$this->setData('current_category', $category);
            }
        }

        return $category;
    }
}

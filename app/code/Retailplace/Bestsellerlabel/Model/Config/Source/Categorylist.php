<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\Bestsellerlabel\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Categorylist implements ArrayInterface
{
    protected $_categoryFactory;
    protected $_categoryCollectionFactory;
    protected $helper;
    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Vdcstore\CategoryTree\Helper\Data $helper
    )
    {
        $this->helper = $helper;
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
    }

    public function getCategoryCollection($isActive = true, $level = false, $sortBy = false, $pageSize = false)
    {
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        $collection->addFieldToFilter('path', ['like' => "1/{$this->helper->getMenuRoot()}/%"]);
        // select only active categories
        if ($isActive) {
            $collection->addIsActiveFilter();
        }

        // select categories of certain level
        if ($level) {
            $collection->addLevelFilter($level);
        }

        // sort categories by some value
        if ($sortBy) {
            $collection->addOrderField($sortBy);
        }

        // select certain number of categories
        if ($pageSize) {
            $collection->setPageSize($pageSize);
        }
        $collection->addAttributeToSelect('name','left');
        return $collection;
    }

    public function toOptionArray()
    {
        $arr = $this->_toArray();
        $ret = [];

        foreach ($arr as $key => $value)
        {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $ret;
    }

    private function _toArray()
    {
        $categories = $this->getCategoryCollection(true, false, false, false);

        $allCategories = (clone $categories)->getSelect();
        $connection = $allCategories->getConnection();
        $allCategoryName = $connection->fetchAssoc($allCategories);
        $categories = $categories->getData();
        $catagoryList = array();
        foreach ($categories as $category)
        {
           $catagoryList[$category['entity_id']] = __($this->_getParentName($category['path'],$allCategoryName) . $category['name']);
        }

        return $catagoryList;
    }

    private function _getParentName($path = '',$allCategoryName)
    {
        $parentName = '';
        $rootCats = array(1,2);

        $catTree = explode("/", $path);
        // Deleting category itself
        array_pop($catTree);

        if($catTree && (count($catTree) > count($rootCats)))
        {
            foreach ($catTree as $catId)
            {
                if(!in_array($catId, $rootCats))
                {
                    if(isset($allCategoryName[$catId])){

                        $categoryName = $allCategoryName[$catId]['name'];
                        $parentName .= $categoryName . ' -> ';
                    }


                }
            }
        }

        return $parentName;
    }
}


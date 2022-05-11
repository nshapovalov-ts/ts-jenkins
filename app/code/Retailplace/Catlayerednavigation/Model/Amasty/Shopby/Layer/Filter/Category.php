<?php

namespace Retailplace\Catlayerednavigation\Model\Amasty\Shopby\Layer\Filter;

use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection as ShopbyFulltextCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Search\Model\SearchEngine;
use Amasty\Shopby\Model\Source\RenderCategoriesLevel;
use Amasty\Shopby\Helper\Category as CategoryHelper;
use Amasty\Shopby\Model\Layer\Filter\Traits\FilterTrait;
use Amasty\Shopby\Model\Source\CategoryTreeDisplayMode;
use Magento\Framework\App\ProductMetadata;


class Category extends \Amasty\Shopby\Model\Layer\Filter\Category
{

	protected function getExtendedCategoryCollection(\Magento\Catalog\Model\Category $startCategory)
    {
        $minLevel = $startCategory->getLevel();
        $maxLevel = $minLevel + $this->getCategoriesTreeDept();

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $startCategory->getCollection();
        $isFlat = $collection instanceof \Magento\Catalog\Model\ResourceModel\Category\Flat\Collection;
        $mainTablePrefix = $isFlat ? 'main_table.' : '';
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter($mainTablePrefix . 'is_active', 1)
			->addAttributeToFilter('include_in_menu', 1)
            ->addFieldToFilter($mainTablePrefix . 'path', ['like' => $startCategory->getPath() . '%'])
            ->addFieldToFilter($mainTablePrefix . 'level', ['gt' => $minLevel])
            ->setOrder(
                $mainTablePrefix . 'position',
                \Magento\Framework\DB\Select::SQL_ASC
            );
        if (!$this->isRenderAllTree()) {
            $collection->addFieldToFilter($mainTablePrefix . 'level', ['lteq' => $maxLevel]);
        }

        $mainTablePrefix = $isFlat ? 'main_table.' : 'e.';
        $collection->getSelect()->joinLeft(
            ['parent' => $collection->getMainTable()],
            $mainTablePrefix . 'parent_id = parent.entity_id',
            ['parent_path' => 'parent.path']
        );

        return $collection;
    }
}

	
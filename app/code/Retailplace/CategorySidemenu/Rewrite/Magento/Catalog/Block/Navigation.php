<?php
/**
 * Category Side Menu
 * Copyright (C) 2019
 *
 * This file is part of Retailplace/CategorySidemenu.
 *
 * Retailplace/CategorySidemenu is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Retailplace_CategorySidemenu
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

namespace Retailplace\CategorySidemenu\Rewrite\Magento\Catalog\Block;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Helper\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Indexer\Category\Flat\State;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Registry;

class Navigation extends \Magento\Catalog\Block\Navigation
{
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Navigation constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param CategoryFactory $categoryFactory
     * @param CollectionFactory $productCollectionFactory
     * @param Resolver $layerResolver
     * @param Context $httpContext
     * @param Category $catalogCategory
     * @param Registry $registry
     * @param State $flatState
     * @param CategoryRepositoryInterface $categoryRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        CategoryFactory $categoryFactory,
        CollectionFactory $productCollectionFactory,
        Resolver $layerResolver,
        Context $httpContext,
        Category $catalogCategory,
        Registry $registry,
        State $flatState,
        CategoryRepositoryInterface $categoryRepository,
        array $data = []
    ) {
        parent::__construct($context, $categoryFactory, $productCollectionFactory, $layerResolver, $httpContext, $catalogCategory, $registry, $flatState, $data);
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCategoryTreeHtml()
    {
        if ($firstLevelCategoryId = $this->getFirstLevelCategory()) {
            $currentCategory = $this->_catalogLayer->getCurrentCategory();
            $pathIds = $currentCategory->getPathIds();
            $_category = $this->categoryRepository->get($firstLevelCategoryId);
            return $this->getCategoryTree($_category, $pathIds);
        }
        return "";
    }

    /**
     * @return false|mixed
     */
    public function getFirstLevelCategory()
    {
        $category = $this->_catalogLayer->getCurrentCategory();
        if ($category->getLevel() >= 2) {
            return $category->getPathIds()[1];
        }
        return false;
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @param array $pathIds
     * @return string
     */
    public function getCategoryTree($category, $pathIds)
    {
        $_categories = $category->getChildrenCategories();
        $_categories
            ->addAttributeToFilter('include_in_menu', 1)
            ->addAttributeToFilter('is_active', 1);

        $html = '<ol class="items">';
        foreach ($_categories as $_category) {
            $categoryUrl = $this->getCategoryUrl($_category);
            if ($_category->getLevel() == 2) {
                $categoryUrl .= '?seller_view=1';
            }
            $html .= '<li class="item">';
            $html .= '<a href="' . $this->escapeUrl($categoryUrl) . '"';
            if ($this->isCategoryActiveSelected($_category)) {
                $html .= 'class="current"';
            }
            $html .= '>' . $this->escapeHtml($_category->getName()) . '</a>';
            if (in_array($_category->getId(), $pathIds)) {
                $html .= $this->getCategoryTree($_category, $pathIds);
            }
            $html .= '</li>';
        }
        $html .= '</ol>';
        return $html;
    }

    /**
     * Check activity of category
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return bool
     */
    public function isCategoryActiveSelected($category)
    {
        if ($this->getCurrentCategory()) {
            return $category->getId() == $this->getCurrentCategory()->getId();
        }
        return false;
    }
}

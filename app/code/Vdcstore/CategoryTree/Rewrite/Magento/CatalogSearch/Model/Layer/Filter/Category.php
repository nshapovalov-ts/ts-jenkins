<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\CategoryTree\Rewrite\Magento\CatalogSearch\Model\Layer\Filter;

use Magento\Catalog\Api\CategoryRepositoryInterface;

class Category extends \Magento\CatalogSearch\Model\Layer\Filter\Category
{
    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;
    /**
     * @var \Magento\Catalog\Model\Layer\Filter\DataProvider\Category
     */
    protected $dataProvider;
    /**
     * @var \Vdcstore\CategoryTree\Helper\Data
     */
    private $helper;
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Category constructor.
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $categoryDataProviderFactory
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $categoryDataProviderFactory,
        \Vdcstore\CategoryTree\Helper\Data $helper,
        CategoryRepositoryInterface $categoryRepository,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $escaper,
            $categoryDataProviderFactory,
            $data
        );
        $this->escaper = $escaper;
        $this->_requestVar = 'cat';
        $this->helper = $helper;
        $this->categoryRepository = $categoryRepository;
        $this->dataProvider = $categoryDataProviderFactory->create(['layer' => $this->getLayer()]);
    }

    /**
     * Apply category filter to product collection
     *
     * @param   \Magento\Framework\App\RequestInterface $request
     * @return  $this
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        $categoryId = $request->getParam($this->_requestVar) ?: $request->getParam('id');
        $origCategory = $this->categoryRepository->get($categoryId);
        $menuRootCategory = $this->helper->getMenuRoot();
        $rootCategory = $this->categoryRepository->get($menuRootCategory);

        if (empty($categoryId)) {
            return $this;
        }

        $this->dataProvider->setCategoryId($rootCategory->getId());

        $category = $this->dataProvider->getCategory();
        if ($origCategory->getChildCategories()) {
            $this->getLayer()->getProductCollection()->addCategoriesFilter(['in' => explode(",", $origCategory->getChildCategories())]);
        } else {
            $this->getLayer()->getProductCollection()->addCategoriesFilter(['in' => [$origCategory->getMappedCategory()]]);
        }

        if ($request->getParam('id') != $category->getId() && $this->dataProvider->isValid()) {
            $this->getLayer()->getState()->addFilter($this->_createItem($category->getName(), $categoryId));
        }

        return $this;
    }
}

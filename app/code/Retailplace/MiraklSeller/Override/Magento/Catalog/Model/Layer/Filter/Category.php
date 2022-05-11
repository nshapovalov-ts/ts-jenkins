<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Retailplace\MiraklSeller\Override\Magento\Catalog\Model\Layer\Filter;

use Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory;

/**
 * Layer attribute filter
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Category extends \Magento\Catalog\Model\Layer\Filter\Category
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;
    /**
     * @var \Retailplace\MiraklSeller\Helper\Data
     */
    private $mirakleSellerHelper;

    /**
     * Category constructor.
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Framework\Escaper $escaper
     * @param CategoryFactory $categoryDataProviderFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Retailplace\MiraklSeller\Helper\Data $mirakleSellerHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Escaper $escaper,
        CategoryFactory $categoryDataProviderFactory,
        \Magento\Framework\App\Request\Http $request,
        \Retailplace\MiraklSeller\Helper\Data $mirakleSellerHelper,
        array $data = []
    ) {
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $escaper, $categoryDataProviderFactory, $data);
        $this->request = $request;
        $this->mirakleSellerHelper = $mirakleSellerHelper;
    }

    /**
     * Get data array for building attribute filter items
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return array
     */
    protected function _getItemsData()
    {
        if ($this->mirakleSellerHelper->getSideMenuConfig('category') && $this->request->getFullActionName() == 'marketplace_shop_view') {
            return  [];
        }
        return  parent::_getItemsData();
    }
}

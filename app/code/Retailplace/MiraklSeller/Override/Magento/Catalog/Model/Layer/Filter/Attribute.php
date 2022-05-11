<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Retailplace\MiraklSeller\Override\Magento\Catalog\Model\Layer\Filter;

/**
 * Layer attribute filter
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Attribute extends \Magento\Catalog\Model\Layer\Filter\Attribute
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
     * Attribute constructor.
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory $filterAttributeFactory
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Filter\StripTags $tagFilter
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Retailplace\MiraklSeller\Helper\Data $mirakleSellerHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory $filterAttributeFactory,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Filter\StripTags $tagFilter,
        \Magento\Framework\App\Request\Http $request,
        \Retailplace\MiraklSeller\Helper\Data $mirakleSellerHelper,
        array $data = []
    ) {
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $filterAttributeFactory, $string, $tagFilter, $data);
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
        if ($this->mirakleSellerHelper->getSideMenuConfig('attribute') && $this->request->getFullActionName() == 'marketplace_shop_view') {
            return  [];
        }
        return  parent::_getItemsData();
    }
}

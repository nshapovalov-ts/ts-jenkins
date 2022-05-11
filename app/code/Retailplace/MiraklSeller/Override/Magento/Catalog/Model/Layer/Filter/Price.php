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
class Price extends \Magento\Catalog\Model\Layer\Filter\Price
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
     * Price constructor.
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory,
        \Magento\Framework\App\Request\Http $request,
        \Retailplace\MiraklSeller\Helper\Data $mirakleSellerHelper,
        array $data = []
    ) {
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $resource, $customerSession, $priceAlgorithm, $priceCurrency, $algorithmFactory, $dataProviderFactory, $data);
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
        if ($this->mirakleSellerHelper->getSideMenuConfig('price') && $this->request->getFullActionName() == 'marketplace_shop_view') {
            return  [];
        }
        return  parent::_getItemsData();
    }
}

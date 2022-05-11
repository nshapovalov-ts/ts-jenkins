<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


namespace Amasty\Shopby\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\IsNew\Helper as IsNewHelper;
use Amasty\Shopby\Model\Layer\Filter\Traits\CustomTrait;
use Magento\Framework\Exception\StateException;
use Magento\Search\Model\SearchEngine;
use Magento\Store\Model\ScopeInterface;

class IsNew extends \Magento\Catalog\Model\Layer\Filter\AbstractFilter
{
    use CustomTrait;

    const FILTER_NEW = 1;
    const FILTER_NOT_NEW = 0;
    const FILTER_LABEL_XML_PATH = 'amshopby/am_is_new_filter/label';
    const FILTER_POSITION_XML_PATH = 'amshopby/am_is_new_filter/position';

    /**
     * @var string
     */
    private $attributeCode = 'am_is_new';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Amasty\Shopby\Model\Request
     */
    private $shopbyRequest;

    /**
     * @var IsNewHelper
     */
    private $isNewHelper;

    /**
     * @var SearchEngine
     */
    private $searchEngine;

    /**
     * @var \Amasty\Shopby\Helper\FilterSetting
     */
    private $settingHelper;

    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Amasty\Shopby\Model\Request $shopbyRequest,
        IsNewHelper $isNewHelper,
        SearchEngine $searchEngine,
        \Amasty\Shopby\Helper\FilterSetting $settingHelper,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $data
        );
        $this->settingHelper = $settingHelper;
        $this->_requestVar = 'am_is_new';
        $this->scopeConfig = $scopeConfig;
        $this->shopbyRequest = $shopbyRequest;
        $this->isNewHelper = $isNewHelper;
        $this->searchEngine = $searchEngine;
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return $this
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        if ($this->isApplied()) {
            return $this;
        }

        $filter = $this->shopbyRequest->getFilterParam($this);

        if (!in_array($filter, [self::FILTER_NEW])) {
            return $this;
        }

        $this->setCurrentValue($filter);

        if ($filter == self::FILTER_NEW) {
            $name = __('Yes');
            $this->getLayer()->getProductCollection()->addFieldToFilter('am_is_new', $filter);
            /**
             * @TODO remove this construction usage after 2.5.1
             * $this->isNewHelper->addNewFilter($this->getLayer()->getProductCollection());
             */
            $this->getLayer()->getState()->addFilter($this->_createItem($name, $filter));
        }

        return $this;
    }

    /**
     * Get filter name
     *
     * @return string
     */
    public function getName()
    {
        return (string)$this->scopeConfig->getValue(
            self::FILTER_LABEL_XML_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return (int)$this->scopeConfig->getValue(
            self::FILTER_POSITION_XML_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get data array for building category filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        if ($this->isHide()) {
            return [];
        }

        try {
            $optionsFacetedData = $this->getFacetedData();
        } catch (StateException $e) {
            $optionsFacetedData = [];
        }

        $newItemsCount = $this->countNewItems($optionsFacetedData);

        if ($newItemsCount > 0) {
            $this->itemDataBuilder->addItemData(__('New'), self::FILTER_NEW, $newItemsCount);
        }

        return $this->itemDataBuilder->build();
    }

    /**
     * @param array $optionsFacetedData
     * @return mixed
     */
    private function countNewItems(array $optionsFacetedData)
    {
        return array_reduce($optionsFacetedData, function ($sum, $item) {
            return isset($item['count']) && $item['value'] != self::FILTER_NOT_NEW
                ? $sum + $item['count']
                : $sum;
        }, 0);
    }
}

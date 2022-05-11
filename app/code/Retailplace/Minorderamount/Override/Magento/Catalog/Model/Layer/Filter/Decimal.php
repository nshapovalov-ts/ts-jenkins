<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Retailplace\Minorderamount\Override\Magento\Catalog\Model\Layer\Filter;

/**
 * Catalog Layer Decimal Attribute Filter Model
 */
class Decimal extends \Magento\Catalog\Model\Layer\Filter\Decimal
{
    /**
     * Resource instance
     *
     * @var \Magento\Catalog\Model\ResourceModel\Layer\Filter\Decimal
     */
    protected $_resource;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var DataProvider\Decimal
     */
    private $dataProvider;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;
    /**
     * @var \Retailplace\MiraklSeller\Helper\Data
     */
    private $mirakleSellerHelper;
    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\DecimalFactory $dataProviderFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\DecimalFactory $dataProviderFactory,
        \Magento\Framework\App\Request\Http $request,
        \Retailplace\MiraklSeller\Helper\Data $mirakleSellerHelper,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $priceCurrency,
            $dataProviderFactory,
            $data
        );
        $this->dataProvider = $dataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->request = $request;
        $this->mirakleSellerHelper = $mirakleSellerHelper;
    }

    /**
     * Prepare text of item label
     *
     * @param   int $range
     * @param   float $value
     * @return \Magento\Framework\Phrase
     */
    protected function _renderItemLabel($range, $value)
    {
        if ($range == 1 & $value == 1) {
            return __("No Minimum Order Amount");
        } elseif ($range == 100 & $value == 1) {
            $from = $this->priceCurrency->format(1, false);
            $to = $this->priceCurrency->format($value * $range, false);
            return __('%1 - %2', $from, $to);
        } else {
            $fromValue = ($value - 1) * $range;
            $toValue = $value * $range;

            if ($fromValue == 100 && $toValue < 200) {
                $fromValue = 1;
            }
            $from = $this->priceCurrency->format($fromValue, false);
            $to = $this->priceCurrency->format($toValue, false);
            return __('%1 - %2', $from, $to);
        }
    }

    /**
     * Retrieve data for build decimal filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        if ($this->mirakleSellerHelper->getSideMenuConfig('decimal') && $this->request->getFullActionName() == 'marketplace_shop_view') {
            return  [];
        }
        if ($this->request->getFullActionName() == 'marketplace_shop_view' && $this->getAttributeModel()->getAttributeCode() == "min_order_amount") {
            return [];
        }
        $range = $this->dataProvider->getRange($this);
        $dbRanges = $this->dataProvider->getRangeItemCounts($range, $this);
        $flag = false;
        foreach ($dbRanges as $index => $count) {
            if ($this->getAttributeModel()->getAttributeCode() == "min_order_amount") {
                if ($index == 0) {
                    $flag = true;
                    $label = $label = $this->_renderItemLabel(1, 1);
                    $this->itemDataBuilder->addItemData(
                        $label,
                        ($index+1) . ',' . 1,
                        $count
                    );
                } elseif ($flag && $index == 1) {
                    $label = $this->_renderItemLabel(100, 1);
                    $this->itemDataBuilder->addItemData(
                        $label,
                        1 . ',' . $range,
                        $count
                    );
                } else {
                    $label = $this->_renderItemLabel($range, $index);
                    $this->itemDataBuilder->addItemData(
                        $label,
                        $index . ',' . $range,
                        $count
                    );
                }
            } else {
                $label = $this->_renderItemLabel($range, $index);
                $this->itemDataBuilder->addItemData(
                    $label,
                    $index . ',' . $range,
                    $count
                );
            }
        }

        return $this->itemDataBuilder->build();
    }
}

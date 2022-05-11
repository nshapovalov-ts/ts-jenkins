<?php
/**
 * Retailplace_Search
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Search\Index\Mirakl;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\Helper\Data;
use Mirasvit\SearchAutocomplete\Index\AbstractIndex;
use Retailplace\Search\Model\SearchFilter;

/**
 * Class Shop
 */
class Shop extends AbstractIndex
{
    /**
     * @var Data
     */
    public $priceHelper;

    /**
     * @var SearchFilter
     */
    private $searchFilter;

    /**
     * @param Data $priceHelper
     * @param SearchFilter $searchFilter
     */
    public function __construct(
        Data $priceHelper,
        SearchFilter $searchFilter
    ) {
        $this->priceHelper = $priceHelper;
        $this->searchFilter = $searchFilter;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems()
    {
        $items = [];

        $this->searchFilter->setModeWildcardAutocomplete();

        /** @var \Mirakl\Core\Model\Shop $shop */
        foreach ($this->getCollection() as $shop) {
            $items[] = $this->mapShop($shop);
        }

        return $items;
    }

    /**
     * @param \Mirakl\Core\Model\Shop $shop
     * @return array
     */
    public function mapShop($shop)
    {
        $minOrderAmount = $shop->getData('min-order-amount');
        $minOrderAmountText = $minOrderAmount > 0 ? __('%1 Minimum Order Amount', $this->formatPrice($minOrderAmount)) :
            __('No Minimum Order Amount');

        $map = [
            'name'             => $shop->getName(),
            'url'              => $shop->getUrl(),
            'logo'             => $shop->getLogo(),
            'min_order_amount' => $minOrderAmountText,
        ];

        return $map;
    }

    /**
     * @param float $price
     * @return float|string
     */
    protected function formatPrice($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * @param array $data
     * @param array $dimensions
     * @return mixed
     */
    public function map($data, $dimensions)
    {
        foreach ($data as $entityId => $itm) {
            $om = ObjectManager::getInstance();
            $entity = $om->create('Mirakl\Core\Model\Shop')->load($entityId);

            $map = $this->mapShop($entity);
            $data[$entityId]['autocomplete'] = $map;
        }

        return $data;
    }
}

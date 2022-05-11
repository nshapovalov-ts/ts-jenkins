<?php

/**
 * Retailplace_Aghaseller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Aghaseller\Helper;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Mirakl\Core\Model\ResourceModel\Shop\Collection as ShopCollection;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Mirakl\Core\Model\Shop;

class Data extends AbstractHelper
{
    const SELLER_PRODUCT_IMAGES_COUNT = 10;

    /**
     * @var ShopCollectionFactory
     */
    protected $shopCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var Status
     */
    protected $productStatus;

    /**
     * @var Visibility
     */
    protected $productVisibility;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param ShopCollectionFactory $shopCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Status $productStatus
     * @param Visibility $productVisibility
     * @param Image $imageHelper
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        ShopCollectionFactory $shopCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        Status $productStatus,
        Visibility $productVisibility,
        Image $imageHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        $this->imageHelper = $imageHelper;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue('aghaseller/aghaseller/enable', $storeScope);
    }

    /**
     * @return mixed
     */
    public function getPageAllowedValues()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue('aghaseller/aghaseller/per_page_values', $storeScope);
    }

    /**
     * @return mixed
     */
    public function getPageDefaultValue()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue('aghaseller/aghaseller/limit', $storeScope);
    }

    /**
     * @return ShopCollection
     */
    public function getShopCollection()
    {
        $shopCollection = $this->getAllShopCollection();
        $this->applyFilterByMinimum($shopCollection);
        $this->applyFilterByName($shopCollection);
        $this->joinSellerImages($shopCollection);
        return $shopCollection;
    }

    /**
     * @return ShopCollection
     */
    public function getAllShopCollection()
    {
        $shopCollection = $this->shopCollectionFactory->create();
        $shopCollection->addFieldToFilter('agha_seller', 1);
        $shopCollection->addFieldToFilter('state', Shop::STATE_OPEN);
        return $shopCollection;
    }

    /**
     * @param ShopCollection $shopCollection
     */
    public function applyFilterByMinimum($shopCollection)
    {
        $minimum = $this->request->getParam('minimum');
        switch ($minimum) {
            case "nominimum":
                $shopCollection->addFieldToFilter('min-order-amount', [['null' => true], 'eq' => 0]);
                break;
            case 100:
                $shopCollection->addFieldToFilter('min-order-amount', ['lteq' => 100]);
                break;
            case 200:
                $shopCollection->addFieldToFilter('min-order-amount', ['lteq' => 200]);
                break;
            case 300:
                $shopCollection->addFieldToFilter('min-order-amount', ['lteq' => 300]);
                break;
            case 301:
                $shopCollection->addFieldToFilter('min-order-amount', ['gt' => 300]);
                break;
            default:
        }
    }

    /**
     * @param ShopCollection $shopCollection
     */
    public function applyFilterByName($shopCollection)
    {
        $shopName = $this->request->getParam('shopname');
        if ($shopName) {
            $shopIds = explode(',', $shopName);
            $shopCollection->addFieldToFilter('id', ['in' => $shopIds]);
        }
    }

    /**
     * @param ShopCollection $shopCollection
     */
    public function joinSellerImages($shopCollection)
    {
        $shopCollection->getSelect()->joinLeft(
            ['plpseller' => $shopCollection->getTable('retailplace_plpseller')],
            'main_table.id = plpseller.seller_id AND plpseller.category_id IS NULL',
            ['plpseller.image']
        );
    }

    /**
     * @param ShopCollection $shopCollection
     */
    public function addProductImages(ShopCollection $shopCollection)
    {
        $shopIdsFilter = [];
        /** @var Shop $shop */
        foreach ($shopCollection as $shop) {
            $shopIdsFilter[] = ['finset' => [$shop->getEavOptionId()]];
        }
        if (!$shopIdsFilter) {
            return;
        }

        /** @var ProductCollection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
        $collection->setVisibility($this->productVisibility->getVisibleInSiteIds());

        $collection->addFieldToFilter('mirakl_shop_ids', $shopIdsFilter);
        $collection->addAttributeToSelect('small_image');

        $images = [];
        /** @var Product $_product */
        foreach ($collection as $_product) {
            $productShopIds = $_product->getMiraklShopIds();
            foreach (explode(',', $productShopIds) as $productShopId) {
                if (isset($images[$productShopId]) && count($images[$productShopId]) >= self::SELLER_PRODUCT_IMAGES_COUNT) {
                    continue;
                }
                $images[$productShopId][] = $this->imageHelper->init($_product, 'category_page_grid')->getUrl();
            }
        }

        /** @var Shop $shop */
        foreach ($shopCollection as $shop) {
            if (isset($images[$shop->getEavOptionId()])) {
                $shop->setProductImages($images[$shop->getEavOptionId()]);
            }
        }
    }
}

<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Helper;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Mirakl\Core\Model\ResourceModel\Shop\Collection as ShopCollection;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Mirakl\Core\Model\Shop;

/**
 * Class Seller
 */
class Seller extends AbstractHelper
{
    /** @var int */
    const SELLER_PRODUCT_IMAGES_COUNT = 5;

    /** @var string */
    const MIRAKL_SELLER_CORE_SELLER_PLP_ENABLE = 'mirakl_seller_core/seller_plp/enable';
    const MIRAKL_SELLER_CORE_SELLER_PLP_PER_PAGE_VALUES = 'mirakl_seller_core/seller_plp/per_page_values';
    const MIRAKL_SELLER_CORE_SELLER_PLP_LIMIT = 'mirakl_seller_core/seller_plp/limit';
    const MIN_ORDER_AMOUNT = 'min-order-amount';
    const SHOPNAME = 'shopname';
    const MINIMUM = 'minimum';
    const EAV_OPTION_ID = 'eav_option_id';
    const STATE = 'state';
    const MIRAKL_SELLER_SHOW_SELLER_NOTIFY_FORM_ENABLE = 'mirakl_seller_core/seller_notify_form/enable';
    const MIRAKL_SELLER_SHOW_SELLER_NOTIFY_FORM_REGION = 'mirakl_seller_core/seller_notify_form/region';
    const MIRAKL_SELLER_SHOW_SELLER_NOTIFY_FORM_PORTAL_ID = 'mirakl_seller_core/seller_notify_form/portal_id';
    const MIRAKL_SELLER_SHOW_SELLER_NOTIFY_FORM_ID = 'mirakl_seller_core/seller_notify_form/form_id';

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
        return $this->scopeConfig->getValue(self::MIRAKL_SELLER_CORE_SELLER_PLP_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getPageAllowedValues()
    {
        return $this->scopeConfig->getValue(self::MIRAKL_SELLER_CORE_SELLER_PLP_PER_PAGE_VALUES, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getPageDefaultValue()
    {
        return $this->scopeConfig->getValue(self::MIRAKL_SELLER_CORE_SELLER_PLP_LIMIT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return ShopCollection
     */
    public function getShopCollection($shopEavIds)
    {
        $shopCollection = $this->getAllShopCollection($shopEavIds);
        $this->applyFilterByMinimum($shopCollection);
        $this->applyFilterByName($shopCollection);
        $this->joinSellerImages($shopCollection);
        return $shopCollection;
    }

    /**
     * @param $shopEavIds
     * @return ShopCollection
     */
    public function getAllShopCollection($shopEavIds)
    {
        $shopCollection = $this->shopCollectionFactory->create();
        $shopCollection->addFieldToFilter(self::EAV_OPTION_ID, ['in' => $shopEavIds]);
        $shopCollection->addFieldToFilter(self::STATE, Shop::STATE_OPEN);
        return $shopCollection;
    }

    /**
     * @param ShopCollection $shopCollection
     */
    public function applyFilterByMinimum($shopCollection)
    {
        $minimum = $this->request->getParam(self::MINIMUM);
        switch ($minimum) {
            case "nominimum":
                $shopCollection->addFieldToFilter(self::MIN_ORDER_AMOUNT, [['null' => true], 'eq' => 0]);
                break;
            case 100:
                $shopCollection->addFieldToFilter(self::MIN_ORDER_AMOUNT, ['lteq' => 100]);
                break;
            case 200:
                $shopCollection->addFieldToFilter(self::MIN_ORDER_AMOUNT, ['lteq' => 200]);
                break;
            case 300:
                $shopCollection->addFieldToFilter(self::MIN_ORDER_AMOUNT, ['lteq' => 300]);
                break;
            case 301:
                $shopCollection->addFieldToFilter(self::MIN_ORDER_AMOUNT, ['gt' => 300]);
                break;
            default:
        }
    }

    /**
     * @param ShopCollection $shopCollection
     */
    public function applyFilterByName($shopCollection)
    {
        $shopName = $this->request->getParam(self::SHOPNAME);
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
     * Add Product Images to Shop Collection
     *
     * @param ShopCollection $shopCollection
     * @param ProductCollection $productCollection
     */
    public function addProductImages(ShopCollection $shopCollection, ProductCollection $productCollection)
    {
        $images = [];
        /** @var Product $product */
        foreach ($productCollection as $product) {
            $productShopIds = $product->getMiraklShopIds();
            if (!$productShopIds) {
                continue;
            }
            foreach (explode(',', $productShopIds) as $productShopId) {
                if (isset($images[$productShopId]) && count($images[$productShopId]) >= self::SELLER_PRODUCT_IMAGES_COUNT) {
                    break;
                }
                $images[$productShopId][] = $this->imageHelper->init($product, 'category_page_grid')->getUrl();
            }
        }

        /** @var Shop $shop */
        foreach ($shopCollection as $shop) {
            if (isset($images[$shop->getEavOptionId()])) {
                $shop->setProductImages($images[$shop->getEavOptionId()]);
            }
        }
    }

    /**
     * Is Enable Seller Notify Form
     *
     * @return bool
     */
    public function isEnableSellerNotifyForm(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::MIRAKL_SELLER_SHOW_SELLER_NOTIFY_FORM_ENABLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Seller Notify Form Region
     *
     * @return string
     */
    public function getSellerNotifyFormRegion(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::MIRAKL_SELLER_SHOW_SELLER_NOTIFY_FORM_REGION,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Seller Notify Form Portal Id
     *
     * @return string
     */
    public function getSellerNotifyFormPortalId(): string
    {
        return $this->scopeConfig->getValue(
            self::MIRAKL_SELLER_SHOW_SELLER_NOTIFY_FORM_PORTAL_ID,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Seller Notify Form Id
     *
     * @return string
     */
    public function getSellerNotifyFormId(): string
    {
        return $this->scopeConfig->getValue(
            self::MIRAKL_SELLER_SHOW_SELLER_NOTIFY_FORM_ID,
            ScopeInterface::SCOPE_STORE
        );
    }
}

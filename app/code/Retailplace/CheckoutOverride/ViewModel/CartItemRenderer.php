<?php

/**
 * Retailplace_CheckoutOverride
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CheckoutOverride\ViewModel;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\OfferFactory;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;
use Mirakl\Core\Model\Shop;
use Magento\Msrp\Helper\Data as MsrpHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Mirakl\FrontendDemo\Helper\Tax as MiraklTaxHelper;
use Magento\Tax\Helper\Data as MagentoTaxHelper;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Retailplace\ChannelPricing\Model\AttributesVisibilityManagement;
use Retailplace\SellerTags\Api\Data\SellerTagsAttributes;
use Psr\Log\LoggerInterface;

/**
 * Class CartItemRenderer
 */
class CartItemRenderer implements ArgumentInterface
{
    /**
     * @var string
     */
    public const UNIT_TYPE_ATTRIBUTE_CODE = 'unit_type';
    public const NUMBER_OF_UNIT_PER_PACK_ATTRIBUTE_CODE = 'number_of_unit_per_pack';
    public const UNIT_TYPE_PACKAGE = 'Package';

    /** @var int */
    private $counter = 0;

    /** @var \Magento\Quote\Api\Data\CartItemInterface */
    private $quoteItem;

    /** @var \Magento\Catalog\Api\Data\ProductInterface */
    private $product;

    /** @var \Mirakl\Connector\Model\Offer */
    private $offer;

    /** @var \Mirakl\Connector\Model\OfferFactory */
    private $offerFactory;

    /** @var \Magento\Msrp\Helper\Data */
    private $msrpHelper;

    /** @var \Mirakl\Core\Model\Shop */
    private $shop;

    /** @var \Magento\Framework\Pricing\PriceCurrencyInterface */
    private $priceCurrency;

    /** @var \Magento\Checkout\Model\Session */
    private $checkoutSession;

    /** @var \Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory */
    private $offerCollectionFactory;

    /** @var \Mirakl\FrontendDemo\Helper\Tax */
    private $miraklTaxHelper;

    /** @var \Magento\Tax\Helper\Data */
    private $magentoTaxHelper;

    /** @var \Mirakl\Core\Model\Shop[] */
    private $shopsList = [];

    /** @var \Mirakl\Connector\Model\Offer[] */
    private $offersList = [];

    /** @var \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory */
    private $shopCollectionFactory;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime */
    private $dateTime;

    /** @var \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement */
    private $attributesVisibilityManagement;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * CartItemRenderer constructor.
     *
     * @param \Mirakl\Connector\Model\OfferFactory $offerFactory
     * @param \Magento\Msrp\Helper\Data $msrpHelper
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory $offerCollectionFactory
     * @param \Mirakl\FrontendDemo\Helper\Tax $miraklTaxHelper
     * @param \Magento\Tax\Helper\Data $magentoTaxHelper
     * @param \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory $shopCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement $attributesVisibilityManagement
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        OfferFactory $offerFactory,
        MsrpHelper $msrpHelper,
        PriceCurrencyInterface $priceCurrency,
        CheckoutSession $checkoutSession,
        OfferCollectionFactory $offerCollectionFactory,
        MiraklTaxHelper $miraklTaxHelper,
        MagentoTaxHelper $magentoTaxHelper,
        ShopCollectionFactory $shopCollectionFactory,
        DateTime $dateTime,
        AttributesVisibilityManagement $attributesVisibilityManagement,
        TimezoneInterface $timezone,
        LoggerInterface $logger
    ) {
        $this->offerFactory = $offerFactory;
        $this->msrpHelper = $msrpHelper;
        $this->priceCurrency = $priceCurrency;
        $this->checkoutSession = $checkoutSession;
        $this->offerCollectionFactory = $offerCollectionFactory;
        $this->miraklTaxHelper = $miraklTaxHelper;
        $this->magentoTaxHelper = $magentoTaxHelper;
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->dateTime = $dateTime;
        $this->attributesVisibilityManagement = $attributesVisibilityManagement;
        $this->timezone = $timezone;
        $this->logger = $logger;
    }

    /**
     * Get Current Shop Url
     *
     * @return string
     */
    public function getShopUrl(): string
    {
        $url = '';
        $shop = $this->getShop();
        if ($shop) {
            $url = $shop->getUrl();
        }
        return $url;
    }

    /**
     * Shop Getter
     *
     * @return \Mirakl\Core\Model\Shop|null
     */
    public function getShop(): ?Shop
    {
        return $this->shop;
    }

    /**
     * Get Current Shop Name
     *
     * @return string
     */
    public function getShopName(): string
    {
        $name = '';
        $shop = $this->getShop();
        if ($shop) {
            $name = $shop->getName();
        }
        return $name;
    }

    /**
     * Quote Item Getter
     *
     * @return \Magento\Quote\Api\Data\CartItemInterface
     */
    public function getQuoteItem(): CartItemInterface
    {
        return $this->quoteItem;
    }

    /**
     * Apply Quote Item and Dependencies
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface|\Magento\Quote\Model\Quote\Item $quoteItem
     */
    public function setQuoteItem(CartItemInterface $quoteItem)
    {
        $this->quoteItem = $quoteItem;
        $this->product = $quoteItem->getProduct();

        $this->collectMiracleData();

        $this->offer = $this->offersList[$quoteItem->getId()] ?? null;
        if ($this->offer) {
            $this->shop = $this->shopsList[$this->offer->getShopId()] ?? null;
        }
    }

    /**
     * Collect Offers and Shops Data for the All Cart
     */
    private function collectMiracleData()
    {
        if (!count($this->shopsList)) {
            $shopIdList = [];
            try {
                $quote = $this->getQuote();
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $quote = null;
            }

            if ($quote) {
                /** @var \Magento\Quote\Model\Quote\Item $item */
                foreach ($quote->getAllItems() as $item) {
                    $offer = null;

                    /** @var \Magento\Quote\Model\Quote\Item\Option $offerCustomOption */
                    $offerCustomOption = $item->getProduct()->getCustomOption('mirakl_offer');
                    if ($offerCustomOption) {
                        $offer = $this->offerFactory->fromJson($offerCustomOption->getValue());
                    }

                    if ($offer) {
                        $this->offersList[$item->getId()] = $offer;
                        $shopIdList[] = $offer->getShopId();
                    }
                }
            }

            $this->collectShops(array_unique($shopIdList));
        }
    }

    /**
     * Get Active Checkout Quote
     *
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getQuote(): CartInterface
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Get all cart items
     *
     * @return \Magento\Quote\Api\Data\CartItemInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteItems(): array
    {
        return $this->getQuote()->getAllItems();
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isQuoteHasError(): bool
    {
        return (bool) $this->getQuote()->getHasError();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getShopsData(): array
    {
        $shopData = [];
        $items = $this->getQuoteItems();
        foreach ($items as $item) {
            if ($item->getParentItemId()) {
                continue;
            }
            $shop = $item->getOffer()->getShop();
            $shopData[$item->getMiraklShopId()]['name'] = $shop->getName();
            $shopData[$item->getMiraklShopId()]['min_order_amount'] = $shop->getData('min-order-amount');
            if (!isset($shopData[$item->getMiraklShopId()]['total'])) {
                $shopData[$item->getMiraklShopId()]['total'] = $item->getBaseRowTotalInclTax();
            } else {
                $shopData[$item->getMiraklShopId()]['total'] += $item->getBaseRowTotalInclTax();
            }
            if ($shopData[$item->getMiraklShopId()]['total'] >= $shop->getData('min-order-amount')) {
                $shopData[$item->getMiraklShopId()]['valid'] = true;
            } else {
                $shopData[$item->getMiraklShopId()]['valid'] = false;
            }
        }
        uasort($shopData, function ($a) {
            return !$a["valid"];
        });

        return $shopData;
    }

    /**
     * Get All Shops for the Cart
     *
     * @param int[] $shopIdList
     */
    private function collectShops(array $shopIdList)
    {
        if (count($shopIdList)) {
            $shopCollection = $this->shopCollectionFactory->create();
            $shopCollection->addFieldToFilter('id', ['in' => $shopIdList]);
            foreach ($shopCollection->getItems() as $shop) {
                $this->shopsList[$shop->getId()] = $shop;
            }
        }
    }

    /**
     * Shared Increment for Cart Item Render
     *
     * @return int
     */
    public function getCounter(): int
    {
        $this->counter++;

        return $this->counter;
    }

    /**
     * Check if can Apply Msrp
     *
     * @return bool
     */
    public function canApplyMsrp(): bool
    {
        try {
            $result = $this->msrpHelper->isShowBeforeOrderConfirm($this->getProduct())
                && $this->msrpHelper->isMinimalPriceLessMsrp($this->getProduct());
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $result = false;
        }

        return $result;
    }

    /**
     * Get Active Product Entity
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product
     */
    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    /**
     * Check if Unit Type is Package
     *
     * @return bool
     */
    public function isUnitTypePackage(): bool
    {
        return $this->getProduct()->getAttributeText(self::UNIT_TYPE_ATTRIBUTE_CODE)
            == self::UNIT_TYPE_PACKAGE;
    }

    /**
     * Get Number of Unit per Pack
     *
     * @return string|bool|null
     */
    public function getNumberOfUnitPerPack()
    {
        $numberOfUnitPerPack = $this->getProduct()->getData(self::NUMBER_OF_UNIT_PER_PACK_ATTRIBUTE_CODE);

        return $numberOfUnitPerPack ?: 1;
    }

    /**
     * Get Minimum Qty for Order within Offer
     *
     * @return int
     */
    public function getMinimumQty(): int
    {
        $result = 1;
        if ($this->getOffer()) {
            $result = $this->getOffer()->getMinOrderQuantity() ?: 1;
        }

        return (int) $result;
    }

    /**
     * Get Active Quote Item Offer
     *
     * @return \Mirakl\Connector\Model\Offer|null
     */
    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    /**
     * Get Currency Symbol
     *
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        return $this->priceCurrency->getCurrencySymbol();
    }

    /**
     * Get Minimum Order Sum within Shop
     *
     * @return int
     */
    public function getMinimumOrderSum(): int
    {
        $minimumAmount = 0;
        $shop = $this->getShop();
        if ($shop) {
            $minimumAmount = (int) $shop->getData('min-order-amount');
        }
        return $minimumAmount;
    }

    /**
     * Get Cart Subtotal for the Current Shop
     *
     * @return float
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCartTotalForShop(): float
    {
        $skuList = $this->getCartSkuListForShop();
        $total = 0;

        $quote = $this->getQuote();
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if (in_array($item->getSku(), $skuList)) {
                $total += $item->getBaseRowTotalInclTax();
            }
        }

        return (float) $total;
    }

    /**
     * Get all Offers with SKU from Quote
     *
     * @return string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCartSkuListForShop(): array
    {
        $skuList = [];
        $shop = $this->getShop();
        if ($shop) {
            $offerCollection = $this->offerCollectionFactory->create();
            $offerCollection->addFieldToFilter('shop_id', ['eq' => $shop->getId()]);
            $offerCollection->addFieldToFilter('product_sku', ['in' => $this->getAllCartSku()]);
            $offerCollection->addFieldToFilter('active', ['eq' => 'true']);
            $offerCollection->addFieldToSelect('product_sku');
            foreach ($offerCollection->getItems() as $item) {
                $skuList[] = $item->getProductSku();
            }
        }

        return $skuList;
    }

    /**
     * Get all SKU List from the Active Quote
     *
     * @return string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getAllCartSku(): array
    {
        $skuList = [];
        $quote = $this->getQuote();
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            $skuList[] = $item->getSku();
        }

        return array_unique($skuList);
    }

    /**
     * Get Shipping Price Excluding Tax
     *
     * @param float $totalShippingPrice
     * @return float
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getShippingPriceExclTax(float $totalShippingPrice): float
    {
        $shippingAddress = $this->getQuote()->getShippingAddress();

        $result = $this->getQuote()->getMiraklIsShippingInclTax()
            ? $this->miraklTaxHelper->getShippingPriceExclTax($totalShippingPrice, $shippingAddress)
            : $totalShippingPrice;

        return (float) $result;
    }

    /**
     * Get Shipping Price Including Tax
     *
     * @param float $totalShippingPrice
     * @return float
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getShippingPriceInclTax(float $totalShippingPrice): float
    {
        $shippingAddress = $this->getQuote()->getShippingAddress();

        $result = $this->getQuote()->getMiraklIsShippingInclTax()
            ? $totalShippingPrice
            : $this->miraklTaxHelper->getShippingPriceInclTax($totalShippingPrice, $shippingAddress);

        return (float) $result;
    }

    /**
     * Get Tax Display Mode
     *
     * @return bool
     */
    public function displayShippingBothPrices(): bool
    {
        return $this->magentoTaxHelper->displayShippingBothPrices();
    }

    /**
     * Get Tax Display Mode
     *
     * @return bool
     */
    public function displayShippingPriceExcludingTax(): bool
    {
        return $this->magentoTaxHelper->displayShippingPriceExcludingTax();
    }

    /**
     * Get Tax Display Mode
     *
     * @return bool
     */
    public function displayShippingPriceIncludingTax(): bool
    {
        return $this->magentoTaxHelper->displayShippingPriceIncludingTax();
    }

    /**
     * Get group id for quote item
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @return string
     */
    public function getItemGroupId($item)
    {
        $offer = $item->getOffer();
        if ($offer) {
            $groupId = $item->getMiraklShopId() . '_' . $offer->getLogisticClass() . '_' . $offer->getData('leadtime_to_ship');
        } else {
            $groupId = $item->getMiraklShopId();
        }
        return $groupId;
    }

    /**
     * Check is shop closed
     *
     * @return bool
     */
    public function isShopHolidayClosed()
    {
        $result = false;
        $shop = $this->getQuoteItem()->getShop();
        $now = $this->dateTime->gmtDate();
        if (
            $now > $shop->getData(SellerTagsAttributes::SHOP_HOLIDAY_CLOSED_FROM)
            && $now < $shop->getData(SellerTagsAttributes::SHOP_HOLIDAY_CLOSED_TO)
        ) {
            $result = true;
        }

        return $result;
    }

    /**
     * Get Attribute Label
     *
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return string
     */
    public function getClosedShopLabel($quoteItem): string
    {
        $result = '';
        $shop = $this->getQuoteItem()->getShop();
        $closedToDate = $shop->getData(SellerTagsAttributes::SHOP_HOLIDAY_CLOSED_TO);
        if ($closedToDate) {
            $label = $this->attributesVisibilityManagement
                ->getAttributeLabelByCode(SellerTagsAttributes::PRODUCT_CLOSED_TO);
            $date = $this->timezone->date(
                strtotime($closedToDate)
            )->format('d/m');
            $result = sprintf($label . ' %s', $date);
        }

        return $result;
    }
}

<?php
namespace Mirakl\FrontendDemo\Model\Checkout;

use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Catalog\Helper\Product\Configuration as ProductConfig;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use Mirakl\FrontendDemo\Helper\Quote\Item as QuoteItemHelper;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var QuoteHelper
     */
    private $quoteHelper;

    /**
     * @var QuoteItemHelper
     */
    private $quoteItemHelper;

    /**
     * @var ConnectorConfig
     */
    private $connectorConfig;

    /**
     * @var ImageBuilder
     */
    private $imageBuilder;

    /**
     * @var ProductConfig
     */
    private $productConfig;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var QuoteUpdater
     */
    private $quoteUpdater;

    /**
     * @param   QuoteHelper             $quoteHelper
     * @param   QuoteItemHelper         $quoteItemHelper
     * @param   ConnectorConfig         $connectorConfig
     * @param   ImageBuilder            $imageBuilder
     * @param   ProductConfig           $productConfig
     * @param   PriceCurrencyInterface  $priceCurrency
     * @param   QuoteUpdater            $quoteUpdater
     */
    public function __construct(
        QuoteHelper $quoteHelper,
        QuoteItemHelper $quoteItemHelper,
        ConnectorConfig $connectorConfig,
        ImageBuilder $imageBuilder,
        ProductConfig $productConfig,
        PriceCurrencyInterface $priceCurrency,
        QuoteUpdater $quoteUpdater
    ) {
        $this->quoteHelper     = $quoteHelper;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->connectorConfig = $connectorConfig;
        $this->imageBuilder    = $imageBuilder;
        $this->productConfig   = $productConfig;
        $this->priceCurrency   = $priceCurrency;
        $this->quoteUpdater    = $quoteUpdater;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $itemsShippingTypes = [];
        /** @var QuoteItem $item */
        foreach ($this->getItems() as $item) {
            $itemsShippingTypes[] = [
                'item'                   => $item->getData(),
                'item_image'             => $this->getItemImage($item)->toHtml(),
                'item_options'           => $this->getItemOptions($item),
                'item_price'             => $this->getItemPrice($item),
                'item_price_incl_tax'    => $this->getItemPriceInclTax($item),
                'shipping_types'         => $this->getItemShippingTypes($item)->toArray(),
                'selected_shipping_type' => $this->getItemSelectedShippingType($item)->toArray(),
            ];
        }

        return [
            'items_shipping_types' => $itemsShippingTypes,
            'is_full_marketplace_quote' => $this->quoteHelper->isFullMiraklQuote(),
        ];
    }

    /**
     * @param   QuoteItem   $quoteItem
     * @return  array
     */
    private function getItemOptions(QuoteItem $quoteItem)
    {
        return $this->productConfig->getOptions($quoteItem);
    }

    /**
     * Retrieve quote item image
     *
     * @param   QuoteItem   $quoteItem
     * @param   string      $imageId
     * @param   array       $attributes
     * @return  Image
     */
    private function getItemImage(QuoteItem $quoteItem, $imageId = 'product_thumbnail_image', $attributes = [])
    {
        return $this->imageBuilder->setProduct($quoteItem->getProduct())
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }

    /**
     * @param   QuoteItem   $quoteItem
     * @return  string
     */
    public function getItemPrice(QuoteItem $quoteItem)
    {
        return $this->priceCurrency->convert($quoteItem->getRowTotal());
    }

    /**
     * @param   QuoteItem   $quoteItem
     * @return  string
     */
    public function getItemPriceInclTax(QuoteItem $quoteItem)
    {
        return $this->priceCurrency->convert($quoteItem->getRowTotalInclTax());
    }

    /**
     * @return  QuoteItem[]
     */
    private function getItems()
    {
        return $this->quoteHelper->getGroupedItems();
    }

    /**
     * @param   QuoteItem   $item
     * @return  ShippingFeeType
     */
    private function getItemSelectedShippingType(QuoteItem $item)
    {
        if ($shippingTypeCode = $item->getMiraklShippingType()) {
            return $this->quoteUpdater->getItemShippingTypeByCode($item, $shippingTypeCode);
        }

        return $this->quoteUpdater->getItemSelectedShippingType($item);
    }

    /**
     * @param   QuoteItem   $item
     * @return  ShippingFeeTypeCollection
     */
    private function getItemShippingTypes(QuoteItem $item)
    {
        return $this->quoteItemHelper->getItemShippingTypes($item);
    }
}

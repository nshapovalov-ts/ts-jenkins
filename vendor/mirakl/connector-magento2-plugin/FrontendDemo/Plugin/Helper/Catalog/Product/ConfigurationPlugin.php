<?php
namespace Mirakl\FrontendDemo\Plugin\Helper\Catalog\Product;

use Magento\Catalog\Helper\Product\Configuration;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Mirakl\Connector\Helper\Offer as OfferHelper;
use Mirakl\Connector\Model\OfferFactory;

class ConfigurationPlugin
{
    /**
     * @var OfferFactory
     */
    private $offerFactory;

    /**
     * @var OfferHelper
     */
    private $offerHelper;

    /**
     * @param   OfferFactory    $offerFactory
     * @param   OfferHelper     $offerHelper
     */
    public function __construct(OfferFactory $offerFactory, OfferHelper $offerHelper)
    {
        $this->offerFactory = $offerFactory;
        $this->offerHelper = $offerHelper;
    }

    /**
     * Retrieve offer information for configurable product
     *
     * @param   Configuration   $subject
     * @param   \Closure        $proceed
     * @param   ItemInterface   $item
     * @return  array
     */
    public function aroundGetCustomOptions(Configuration $subject, \Closure $proceed, ItemInterface $item)
    {
        $product = $item->getProduct();

        /** @var \Magento\Quote\Model\Quote\Item\Option $offerCustomOption */
        if ($offerCustomOption = $product->getCustomOption('mirakl_offer')) {
            $offer = $this->offerFactory->fromJson($offerCustomOption->getValue());
            $attributes = [
                [
                    'label' => __('Shop'),
                    'value' => $offer->getShopName(),
                ],
                [
                    'label' => __('Condition'),
                    'value' => $this->offerHelper->getOfferCondition($offer),
                ]
            ];

            return array_merge($attributes, $proceed($item));
        }

        return $proceed($item);
    }
}
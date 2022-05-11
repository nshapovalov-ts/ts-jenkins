<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Retailplace\MiraklConnector\Rewrite\Helper\Offer as OfferHelper;

class Data extends AbstractHelper
{
    /** @var string */
    const XML_PATH_SHIPPING_FEE_PERCENT = 'mirakl_frontend/general/shipping_fee_percent';
    const MIRAKL_FRONTEND_GENERAL_TEXT_FOR_HIGH_MARGIN = 'mirakl_frontend/general/text_for_above_80_margin';

    /** @var int */
    const MARGIN_THRESHOLD_HIGH = 80;
    const MARGIN_THRESHOLD_SHOW = 40;

    /**
     * @var string
     */
    protected $productHtml;
    /**
     * @var string
     */
    protected $textForHighMargin;

    /**
     * @param Product $product
     * @return string
     */
    public function getCalculatedMargin(Product $product)
    {
        $html = "";

        /** @var \Mirakl\Connector\Model\Offer $bestOffer */
        $bestOffer = $product->getData('main_offer');
        $margin = round($product->getData('retail_margin'));
        if ($bestOffer) {
            $offerPrice = $bestOffer->getPrice();
            $offerProduct = $bestOffer->getData(OfferHelper::OFFER_PRODUCT);
            $retailPrice = $offerProduct instanceof Product ? $offerProduct->getRetailPrice() : $product->getRetailPrice();

            if (is_numeric($retailPrice) && $retailPrice > 0) {
                $margin = round((($retailPrice - $offerPrice) / $retailPrice) * 100);
            }
        }

        if ($margin) {
            if ($margin > self::MARGIN_THRESHOLD_HIGH) {
                $html = '<span class="margin">' . __($this->getTextForHighMargin()) . '</span>';
            } elseif ($margin >= self::MARGIN_THRESHOLD_SHOW) {
                $html = '<span class="margin">' . __(' %1% Margin', $margin) . '</span>';
            }
        }
        return $html;
    }

    /**
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getTextForHighMargin($store = null)
    {
        if ($this->textForHighMargin == null) {
            $this->textForHighMargin = $this->scopeConfig->getValue(
                self::MIRAKL_FRONTEND_GENERAL_TEXT_FOR_HIGH_MARGIN,
                ScopeInterface::SCOPE_STORE,
                $store
            );
        }
        return $this->textForHighMargin;
    }

    /**
     * @param null|string|bool|int|Store $store
     * @return float
     */
    public function getShippingFeePercent($store = null)
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_SHIPPING_FEE_PERCENT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param Product $_product
     * @param string $minimum
     * @return string
     */
    public function getMinimumQtyHtml($_product, $minimum)
    {
        $productId = $_product->getId();
        if (!($productId && $minimum > 1)) {
            return "";
        }
        if (!isset($this->productHtml[$productId])) {
            $this->productHtml[$productId] = "<div class='minimum'>";
            if ($minimum > 1) {
                $unit_type = $_product->getResource()->getAttribute('unit_type')->getFrontend()->getValue($_product);

                if ($unit_type == 'Package') {
                    $number_of_unit_per_pack = $_product->getResource()->getAttribute('number_of_unit_per_pack')->getFrontend()->getValue($_product);
                    if ($number_of_unit_per_pack) {
                        $minimumPack = 1;
                        if ($number_of_unit_per_pack < $minimum) {
                            $minimumPack = ceil($minimum / $number_of_unit_per_pack);
                            $minimumQuantity = $minimumPack * $number_of_unit_per_pack;
                        } else {
                            $minimumQuantity = $number_of_unit_per_pack;
                        }
                        $this->productHtml[$productId] .= __('Min %1 pack (%2 Items)', $minimumPack, $minimumQuantity);
                    } else {
                        $this->productHtml[$productId] .= __('Minimum : %1 Items', $minimum);
                    }
                } else {
                    $this->productHtml[$productId] .= __('Minimum: %1 Items', $minimum);
                }
            }
            $this->productHtml[$productId] .= "</div>";
        }
        return $this->productHtml[$productId];
    }
}

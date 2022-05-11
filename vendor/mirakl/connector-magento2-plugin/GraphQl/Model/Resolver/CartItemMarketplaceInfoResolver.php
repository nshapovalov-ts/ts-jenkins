<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote\Item;

class CartItemMarketplaceInfoResolver implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Item $cartItem */
        $cartItem = $value['model'];

        if (!$cartItem->getMiraklOfferId()) {
            return [];
        }

        $baseCurrency = $cartItem->getQuote()->getBaseCurrencyCode();
        $currency = $cartItem->getQuote()->getQuoteCurrencyCode();

        return [
            'offer_id'                        => $cartItem->getMiraklOfferId(),
            'shop_id'                         => $cartItem->getMiraklShopId(),
            'shop_name'                       => $cartItem->getMiraklShopName(),
            'leadtime_to_ship'                => $cartItem->getMiraklLeadtimeToShip(),
            'shipping_type'                   => $cartItem->getMiraklShippingType(),
            'shipping_type_label'             => $cartItem->getMiraklShippingTypeLabel(),
            'base_shipping_fee'               => ['value' => $cartItem->getMiraklBaseShippingFee(), 'currency' => $baseCurrency],
            'shipping_fee'                    => ['value' => $cartItem->getMiraklShippingFee(), 'currency' => $currency],
            'shipping_tax_percent'            => $cartItem->getMiraklShippingTaxPercent(),
            'base_shipping_tax_amount'        => ['value' => $cartItem->getMiraklBaseShippingTaxAmount(), 'currency' => $baseCurrency],
            'shipping_tax_amount'             => ['value' => $cartItem->getMiraklShippingTaxAmount(), 'currency' => $currency],
            'shipping_tax_applied'            => $cartItem->getMiraklShippingTaxApplied() ? json_encode(unserialize($cartItem->getMiraklShippingTaxApplied())) : null,
            'custom_tax_applied'              => $cartItem->getMiraklCustomTaxApplied() ? json_encode(unserialize($cartItem->getMiraklCustomTaxApplied())) : null,
            'base_custom_shipping_tax_amount' => ['value' => $cartItem->getMiraklBaseCustomShippingTaxAmount(), 'currency' => $baseCurrency],
            'custom_shipping_tax_amount'      => ['value' => $cartItem->getMiraklCustomShippingTaxAmount(), 'currency' => $currency],
        ];
    }
}

<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Offer;

use Magento\CatalogGraphQl\Model\Resolver\Product\Price\Discount;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Api\Data\StoreInterface;
use Mirakl\Connector\Model\Offer;
use Mirakl\GraphQl\Model\Resolver\AbstractResolver;

class PriceResolver extends AbstractResolver implements ResolverInterface
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var Discount
     */
    private $discount;

    /**
     * @param   PriceCurrencyInterface  $priceCurrency
     * @param   Discount                $discount
     */
    public function __construct(PriceCurrencyInterface $priceCurrency, Discount $discount)
    {
        $this->priceCurrency = $priceCurrency;
        $this->discount = $discount;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"%1" value should be specified', 'model'));
        }

        if (!isset($value['product_model'])) {
            throw new LocalizedException(__('"%1" value should be specified', 'product_model'));
        }

        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();

        /** @var Offer $offer */
        $offer = $value['model'];

        $regularPrice = $this->priceCurrency->convertAndRound($offer->getOriginPrice());
        $finalPrice = $this->priceCurrency->convertAndRound($offer->getPrice());

        $priceArray = $this->formatPrice($regularPrice, $finalPrice, $store);
        $priceArray['model'] = $value['product_model'];

        return $priceArray;
    }

    /**
     * @param   float           $regularPrice
     * @param   float           $finalPrice
     * @param   StoreInterface  $store
     * @return  array
     */
    private function formatPrice(float $regularPrice, float $finalPrice, StoreInterface $store): array
    {
        return [
            'regular_price' => [
                'value' => $regularPrice,
                'currency' => $store->getCurrentCurrencyCode(),
            ],
            'final_price' => [
                'value' => $finalPrice,
                'currency' => $store->getCurrentCurrencyCode(),
            ],
            'discount' => $this->discount->getDiscountByDifference($regularPrice, $finalPrice),
        ];
    }
}

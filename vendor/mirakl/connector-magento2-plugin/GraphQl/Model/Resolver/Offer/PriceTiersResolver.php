<?php
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
use Mirakl\MMP\Common\Domain\DiscountRange;

class PriceTiersResolver extends AbstractResolver implements ResolverInterface
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
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();

        /** @var Offer $offer */
        $offer = $value['model'];

        $priceRanges = [];
        foreach ($offer->getPriceRanges() as $priceRange) {
            $priceRanges[$priceRange->getQuantityThreshold()] = $priceRange->getPrice();
        }

        if ($offer->isDiscountPriceValid()) {
            foreach ($offer->getDiscount()->getRanges() as $discountRange) {
                $qty = $discountRange->getQuantityThreshold();
                if (!isset($priceRanges[$qty]) || $priceRanges[$qty]  >$discountRange->getPrice()) {
                    $priceRanges[$qty] = $discountRange->getPrice();
                }
            }
        }

        ksort($priceRanges, SORT_NUMERIC);
        $tiers = [];
        $currentPrice = $offer->getOriginPrice();
        /** @var DiscountRange $priceRange */
        foreach ($priceRanges as $qty => $price) {
            if ($qty <= 1 || $price >= $currentPrice) {
                continue;
            }

            $offerPrice = $this->priceCurrency->convertAndRound($offer->getOriginPrice());
            $tierPrice = $this->priceCurrency->convertAndRound($price);

            $tiers[] = $this->formatOfferTierPrice($offerPrice, $tierPrice, $qty, $store);
            $currentPrice = $price;
        }

        return $tiers;
    }

    /**
     * @param   float           $offerPrice
     * @param   float           $tierPrice
     * @param   int             $qty
     * @param   StoreInterface  $store
     * @return  array
     */
    private function formatOfferTierPrice(float $offerPrice, float $tierPrice, int $qty, StoreInterface $store)
    {
        $discount = $this->discount->getDiscountByDifference($offerPrice, $tierPrice);

        return [
            "discount" => $discount,
            "quantity" => $qty,
            "final_price" => [
                "value" => $tierPrice,
                "currency" => $store->getCurrentCurrencyCode()
            ]
        ];
    }
}

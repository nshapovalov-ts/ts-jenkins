<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mirakl\Connector\Helper\Offer as OfferHelper;
use Mirakl\GraphQl\Model\Mapper\Offer as OfferMapper;

class ProductMarketplaceInfoResolver implements ResolverInterface
{
    /**
     * @var OfferHelper
     */
    private $offerHelper;

    /**
     * @var OfferMapper
     */
    private $offerMapper;

    /**
     * @param OfferHelper $offerHelper
     * @param OfferMapper $offerMapper
     */
    public function __construct(OfferHelper $offerHelper, OfferMapper $offerMapper)
    {
        $this->offerHelper = $offerHelper;
        $this->offerMapper = $offerMapper;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var Product $product */
        $product = $value['model'];

        $offers = $this->offerHelper->getAvailableOffersForProduct($product);
        $offersInfo = [];
        foreach ($offers as $offer) {
            $offersInfo[] = $this->offerMapper->toGraphQlArray($product, $offer);
        }

        return ['offers' => $offersInfo];
    }
}

<?php
declare(strict_types=1);

namespace Mirakl\Connector\Model\InventorySales\IsProductSalableCondition;

use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Mirakl\Connector\Model\ResourceModel\Offer\Collection as OfferCollection;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;

/**
 * @inheritdoc
 */
class IsSalableWithOfferCondition implements IsProductSalableInterface
{
    /**
     * @var OfferCollectionFactory
     */
    private $offerCollectionFactory;

    /**
     * @param  OfferCollectionFactory  $offerCollectionFactory
     */
    public function __construct(OfferCollectionFactory $offerCollectionFactory)
    {
        $this->offerCollectionFactory = $offerCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        /** @var OfferCollection $collection */
        $collection = $this->offerCollectionFactory->create();

        $offers = $collection->addProductSkuFilter($sku)->addAvailableFilter();

        return count($offers) > 0;
    }
}

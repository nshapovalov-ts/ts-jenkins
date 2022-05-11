<?php
declare(strict_types=1);

namespace Mirakl\Connector\Model\InventorySales\IsProductSalableForRequestedQtyCondition;

use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Mirakl\Connector\Model\ResourceModel\Offer\Collection as OfferCollection;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;

/**
 * @inheritdoc
 */
class IsSalableWithOfferCondition implements IsProductSalableForRequestedQtyInterface
{
    /**
     * @var ProductSalabilityErrorInterfaceFactory
     */
    private $productSalabilityErrorFactory;

    /**
     * @var ProductSalableResultInterfaceFactory
     */
    private $productSalableResultFactory;

    /**
     * @var OfferCollectionFactory
     */
    private $offerCollectionFactory;

    /**
     * @param   ProductSalabilityErrorInterfaceFactory  $productSalabilityErrorFactory
     * @param   ProductSalableResultInterfaceFactory    $productSalableResultFactory
     * @param   OfferCollectionFactory                  $offerCollectionFactory
     */
    public function __construct(
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        ProductSalableResultInterfaceFactory $productSalableResultFactory,
        OfferCollectionFactory $offerCollectionFactory
    ) {
        $this->productSalabilityErrorFactory = $productSalabilityErrorFactory;
        $this->productSalableResultFactory = $productSalableResultFactory;
        $this->offerCollectionFactory = $offerCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId, float $requestedQty): ProductSalableResultInterface
    {
        $errors = [];

        /** @var OfferCollection $collection */
        $collection = $this->offerCollectionFactory->create();
        $offers = $collection->addProductSkuFilter($sku)->addAvailableFilter();

        if (!count($offers)) {
            $errors = [
                $this->productSalabilityErrorFactory->create([
                    'code'    => 'is_salable_with_offers-not_available',
                    'message' => __('The requested product has no offer associated'),
                ]),
            ];
        }

        return $this->productSalableResultFactory->create(['errors' => $errors]);
    }
}

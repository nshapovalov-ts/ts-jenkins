<?php
namespace Mirakl\Connector\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Connector\Helper\Offer as OfferHelper;

class ProductIsSalableAfterObserver implements ObserverInterface
{
    /**
     * @var OfferHelper
     */
    private $offerHelper;

    /**
     * @param OfferHelper $offerHelper
     */
    public function __construct(OfferHelper $offerHelper)
    {
        $this->offerHelper = $offerHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var DataObject $salable */
        $salable = $observer->getEvent()->getSalable();

        if (!$salable->getIsSalable()) {
            /** @var Product $product */
            $product = $observer->getEvent()->getProduct();

            // Check if product has active offers and force its stock state to true
            $salable->setIsSalable($this->offerHelper->hasAvailableOffersForProduct($product));
        }
    }
}
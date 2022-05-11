<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Rewrite\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Mirakl\Connector\Helper\Offer as OfferHelper;
use Mirakl\Connector\Observer\ProductIsSalableAfterObserver as MiraklProductIsSalableAfterObserver;

class ProductIsSalableAfterObserver extends MiraklProductIsSalableAfterObserver
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
        parent::__construct($offerHelper);
        $this->offerHelper = $offerHelper;
    }

    /**
     * Set Product not salable if it has no active Offers
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var DataObject $salable */
        $salable = $observer->getEvent()->getSalable();

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $isSalable = $this->offerHelper->hasAvailableOffersForProduct($product);
        $salable->setData('is_salable', $isSalable);
        $product->setData('is_salable', $isSalable);
    }
}

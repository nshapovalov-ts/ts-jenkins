<?php
namespace Mirakl\FrontendDemo\Plugin\Pricing\Price\ConfigurableProduct;

use Magento\ConfigurableProduct\Pricing\Price\FinalPrice;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;

class FinalPricePlugin
{
    /**
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @param   OfferHelper $offerHelper
     */
    public function __construct(OfferHelper $offerHelper)
    {
        $this->offerHelper = $offerHelper;
    }

    /**
     * @param   FinalPrice  $subject
     * @param   \Closure    $proceed
     * @return  float
     */
    public function aroundGetValue(FinalPrice $subject, \Closure $proceed)
    {
        // Return offer price if configurable product is full marketplace
        if ($offer = $this->offerHelper->getBestOffer($subject->getProduct())) {
            return $offer->getPrice();
        }

        return $proceed();
    }
}
<?php
namespace Mirakl\FrontendDemo\Plugin\Pricing\Price\Catalog;

use Magento\Catalog\Pricing\Price\TierPrice;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;

class TierPricePlugin
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
     * @param   TierPrice   $subject
     * @param   \Closure    $proceed
     * @return  array
     */
    public function aroundGetTierPriceList(TierPrice $subject, \Closure $proceed)
    {
        // Do not return tier prices if operator product is not available
        if (!$this->offerHelper->isOperatorProductAvailable($subject->getProduct())) {
            return [];
        }

        return $proceed();
    }
}
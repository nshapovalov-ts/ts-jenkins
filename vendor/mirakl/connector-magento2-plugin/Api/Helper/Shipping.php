<?php
namespace Mirakl\Api\Helper;

use Mirakl\MMP\Common\Domain\Shipping\CustomerShippingToAddress;
use Mirakl\MMP\Front\Domain\Collection\Shipping\OrderShippingFeeCollection;
use Mirakl\MMP\Front\Request\Shipping\GetShippingRatesRequest;

class Shipping extends ClientHelper\MMP
{
    /**
     * (SH02) List shipping rates of offers
     *
     * @param   string  $zone
     * @param   array   $offers
     * @param   string  $locale
     * @param   bool    $computeOrderTaxes
     * @param   array   $customerShippingToAddress
     * @return  OrderShippingFeeCollection
     */
    public function getShippingFees($zone, array $offers, $locale = null, $computeOrderTaxes = false, array $customerShippingToAddress = [])
    {
        $fees = new OrderShippingFeeCollection();
        if (!empty($offers)) {
            $request = new GetShippingRatesRequest($zone, $offers);
            $request->setLocale($this->validateLocale($locale));

            if ($computeOrderTaxes && !empty($customerShippingToAddress)) {
                $request->setComputeOrderTaxes(true);
                $customerShippingToAddress = new CustomerShippingToAddress($customerShippingToAddress);
                $request->setCustomerShippingToAddress($customerShippingToAddress);
            }

            $this->_eventManager->dispatch('mirakl_api_get_shipping_rates_before', [
                'request' => $request,
                'zone'    => $zone,
                'offers'  => $offers,
                'locale'  => $locale,
            ]);

            $fees = $this->send($request);
        }

        return $fees;
    }
}

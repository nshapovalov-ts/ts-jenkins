<?php
namespace Mirakl\Connector\Plugin\Model\Carrier;

use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\CarrierFactory;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Model\Quote\Loader as QuoteLoader;

class CarrierFactoryPlugin
{
    /**
     * @var QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @var QuoteLoader
     */
    protected $quoteLoader;

    /**
     * @param QuoteHelper $quoteHelper
     * @param QuoteLoader $quoteLoader
     */
    public function __construct(
        QuoteHelper $quoteHelper,
        QuoteLoader $quoteLoader
    ) {
        $this->quoteHelper = $quoteHelper;
        $this->quoteLoader = $quoteLoader;
    }

    /**
     * @param   CarrierFactory  $subject
     * @param   \Closure        $proceed
     * @param   string          $carrierCode
     * @return  AbstractCarrier|false
     */
    public function aroundGetIfActive(CarrierFactory $subject, \Closure $proceed, $carrierCode)
    {
        $quote = $this->quoteLoader->getQuote();

        if ($carrierCode == 'freeshipping' && $this->quoteHelper->isFullMiraklQuote($quote)) {
            return $subject->get($carrierCode);
        }

        return $proceed($carrierCode);
    }

    /**
     * @param   CarrierFactory  $subject
     * @param   \Closure        $proceed
     * @param   string          $carrierCode
     * @param   null|int        $storeId
     * @return  AbstractCarrier|false
     */
    public function aroundCreateIfActive(CarrierFactory $subject, \Closure $proceed, $carrierCode, $storeId = null)
    {
        $quote = $this->quoteLoader->getQuote();

        if ($carrierCode == 'freeshipping' && $this->quoteHelper->isFullMiraklQuote($quote)) {
            return $subject->create($carrierCode, $storeId);
        }

        return $proceed($carrierCode, $storeId);
    }
}

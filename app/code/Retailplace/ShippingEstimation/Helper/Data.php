<?php

namespace Retailplace\ShippingEstimation\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\DataObject;
use Mirakl\Core\Helper\ShippingZone;
use Mirakl\Core\Model\Shop;

class Data extends AbstractHelper
{
    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomerAddress
     */
    protected $currentCustomerAddress;

    /**
     * @var ShippingZone
     */
    protected $shippingZone;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var string
     */
    protected $zone;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Helper\Session\CurrentCustomerAddress $currentCustomerAddress
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param ShippingZone $shippingZone
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Helper\Session\CurrentCustomerAddress $currentCustomerAddress,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        ShippingZone $shippingZone
    ) {
        parent::__construct($context);
        $this->currentCustomerAddress = $currentCustomerAddress;
        $this->shippingZone = $shippingZone;
        $this->httpContext = $httpContext;
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * @return false|string
     */
    public function getZone()
    {
        if ($this->zone === null) {
            try {
                $shippingAddress = $this->currentCustomerAddress->getDefaultShippingAddress();
                if (!$shippingAddress) {
                    $this->zone = false;
                } else {
                    $quote = $this->quoteFactory->create();
                    $quote->isVirtual(false);
                    $quoteShippingAddress = $quote->getShippingAddress();
                    $quoteShippingAddress->setPostcode($shippingAddress->getPostcode());
                    $this->zone = $this->shippingZone->getShippingZoneCode($quote->getShippingAddress());
                }
            } catch (\Exception $e) {
            }
        }
        return $this->zone;
    }

    /**
     * @param Shop $shop
     * @return DataObject
     */
    public function getFreeShippingData($shop)
    {
        $additionalInfo = $shop->getAdditionalInfo();
        if (!$additionalInfo) {
            return null;
        }
        $shippingInfo = $additionalInfo->getShippingInfo();
        if (!isset($shippingInfo['shipping_rules'])) {
            return null;
        }

        $shippingRules = $shippingInfo['shipping_rules'];
        $quoteShippingZoneCode = $this->getZone();
        $freeShippingData = null;
        foreach ($shippingRules as $shippingRule) {
            if (!isset($shippingRule['shipping_free_amount']) || !isset($shippingRule['shipping_zone']['code'])) {
                continue;
            }

            $freeShippingAmount = $shippingRule['shipping_free_amount'];
            $shippingZoneCode = $shippingRule['shipping_zone']['code'];

            if ($quoteShippingZoneCode != $shippingZoneCode) {
                continue;
            }

            $shippingTypeLabel = isset($shippingRule['shipping_type']['label']) ? $shippingRule['shipping_type']['label'] : '';

            if ($freeShippingData && $freeShippingAmount >= $freeShippingData->getAmount()) {
                continue;
            }

            $freeShippingData = new DataObject([
                'amount' => $freeShippingAmount,
                'label'  => $shippingTypeLabel
            ]);
        }
        return $freeShippingData;
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }
}

<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use StripeIntegration\Payments\Model\Config;
use Magento\Quote\Model\Quote;
use Retailplace\Stripe\Model\Processing;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class PaymentMethodActiveObserver
 */
class PaymentMethodActiveObserver extends AbstractDataAssignObserver
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Processing
     */
    private $processing;

    /**
     * @var false
     */
    private $hasInvoicing;

    /**
     * @var array
     */
    private $net30Info;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * PaymentMethodActiveObserver constructor.
     * @param Config $config
     * @param Processing $processing
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Config $config,
        Processing $processing,
        DataPersistorInterface $dataPersistor
    ) {
        $this->config = $config;
        $this->processing = $processing;
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->config->isInvoicingEnabled()) {
            return;
        }

        $result = $observer->getEvent()->getResult();
        $methodInstance = $observer->getEvent()->getMethodInstance();
        $quote = $observer->getEvent()->getQuote();
        $code = $methodInstance->getCode();
        $isAvailable = $result->getData('is_available');

        // No need to check if its already false
        if (!$isAvailable) {
            return;
        }

        // Can't check without a quote
        if (!$quote) {
            return;
        }

        if (!preg_match('/^stripe_payments/', $code)) {
            return;
        }

        if (!in_array($code, ["stripe_payments", "stripe_payments_checkout_card", "stripe_payments_invoice"])) {
            return;
        }

        // Disable all other payment methods if we have invoicing
        $isInvoicingStatus = $this->hasInvoicing($quote);

        if ($code == "stripe_payments_invoice") {
            if (!$isInvoicingStatus) {
                $result->setData('is_available', false);
            }
            return;
        }

        if ($code == "stripe_payments" && !empty($this->net30Info)
            && (!$isInvoicingStatus || $this->net30Info['duty'] > 0)) {
            $this->dataPersistor->set("stripe_payment_info", $this->net30Info);
        }

        if ($isInvoicingStatus && $this->config->isDisableCC()) {
            $result->setData('is_available', false);
        }
    }

    /**
     * Has Invoicing
     *
     * @param Quote $quote
     * @return bool
     */
    public function hasInvoicing(Quote $quote): bool
    {
        if ($this->hasInvoicing !== null) {
            return $this->hasInvoicing;
        }

        if (!$this->config->isInvoicingEnabled()) {
            return $this->hasInvoicing = false;
        }

        $customer = $quote->getCustomer();
        $limitInfo = $this->processing->calculateCustomerAvailableCreditLimit($customer);
        if (!isset($limitInfo['available'])) {
            return $this->hasInvoicing = true;
        }

        $grandTotal = (float) $quote->getBaseGrandTotal();
        if ($grandTotal > $limitInfo['available']) {
            $this->net30Info = $limitInfo;
            return $this->hasInvoicing = false;
        }

        return $this->hasInvoicing = true;
    }
}

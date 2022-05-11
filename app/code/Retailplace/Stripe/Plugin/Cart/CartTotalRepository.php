<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Plugin\Cart;

use Magento\Quote\Api\Data\TotalsExtensionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Quote\Model\Cart\Totals;
use Magento\Quote\Model\Cart\CartTotalRepository as QuoteCartTotalRepository;
use Retailplace\Stripe\Model\PaymentInfoFactory;

/**
 * Fix Magento bug on checkout API.
 * Insert discount breakdown data.
 */
class CartTotalRepository
{

    /**
     * @var string
     */
    const EXTENSION_ATTRIBUTE_NAME = "stripe_payment_info";

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var TotalsExtensionFactory
     */
    private $totalsExtensionFactory;

    /**
     * @var PaymentInfoFactory
     */
    private $paymentInfoFactory;

    /**
     * @param DataPersistorInterface $dataPersistor
     * @param TotalsExtensionFactory $totalsExtensionFactory
     * @param PaymentInfoFactory $paymentInfoFactory
     */
    public function __construct(
        DataPersistorInterface $dataPersistor,
        TotalsExtensionFactory $totalsExtensionFactory,
        PaymentInfoFactory $paymentInfoFactory
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->totalsExtensionFactory = $totalsExtensionFactory;
        $this->paymentInfoFactory = $paymentInfoFactory;
    }

    /**
     * Set Stripe Payment Information To Totals
     *
     * @param QuoteCartTotalRepository $subject
     * @param Totals $quoteTotals
     *
     * @return Totals
     */
    public function afterGet(QuoteCartTotalRepository $subject, Totals $quoteTotals)
    {
        $info = $this->dataPersistor->get(self::EXTENSION_ATTRIBUTE_NAME);

        if (!empty($info) && is_array($info)) {
            $extensionAttributes = $quoteTotals->getExtensionAttributes();

            if (!$extensionAttributes) {
                $extensionAttributes = $this->totalsExtensionFactory->create();
            }

            $paymentInfo = $this->paymentInfoFactory->create();
            $paymentInfo->setAvailable($info['available']);
            $paymentInfo->setDuty($info['duty']);
            $paymentInfo->setTotal($info['total']);

            $extensionAttributes->setStripePaymentInfo($paymentInfo);
            $quoteTotals->setExtensionAttributes($extensionAttributes);
            $this->dataPersistor->clear(self::EXTENSION_ATTRIBUTE_NAME);
        }

        return $quoteTotals;
    }
}

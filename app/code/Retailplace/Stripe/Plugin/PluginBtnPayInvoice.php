<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Plugin;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Block\Adminhtml\Order\Invoice\View;
use Retailplace\Stripe\Rewrite\Model\Method\Invoice as StripeInvoice;

/**
 * Class PluginBtnPayInvoice
 */
class PluginBtnPayInvoice
{

    /**
     * @var UrlInterface
     */
    protected $backendUrl;

    /**
     * PluginBtnPayInvoice constructor.
     *
     * @param UrlInterface $backendUrl
     */
    public function __construct(
        UrlInterface $backendUrl
    ) {
        $this->backendUrl = $backendUrl;
    }

    /**
     * Before Set Layout
     *
     * @param View $subject
     * @return null
     */
    public function beforeSetLayout(View $subject)
    {
        $invoice = $subject->getInvoice();
        if (empty($invoice)) {
            return null;
        }

        $stripeInvoiceId = $invoice->getStripeInvoiceId();
        if (empty($stripeInvoiceId)) {
            return null;
        }

        $status = $invoice->getStripeInvoicePaid();
        if ($status == StripeInvoice::STRIPE_INVOICE_PAID) {
            return null;
        }

        $payInvoiceUrl = $this->backendUrl->getUrl(
            'retailplace_stripe/invoice/pay/invoice_id/' . $subject->getInvoice()->getId()
        );

        $subject->addButton(
            'stripe-pay-invoice',
            [
                'label'   => __('Pay Invoice'),
                'onclick' => "setLocation('" . $payInvoiceUrl . "')",
                'class'   => 'ship primary'
            ]
        );

        return null;
    }
}

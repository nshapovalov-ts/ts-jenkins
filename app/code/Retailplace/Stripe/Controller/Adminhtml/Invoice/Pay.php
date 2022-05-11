<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Controller\Adminhtml\Invoice;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Invoice\AbstractInvoice\View;
use Magento\Framework\Exception\LocalizedException;
use Exception;
use Retailplace\Stripe\Rewrite\Model\Method\Invoice as StripeInvoice;
use StripeIntegration\Payments\Model\Config;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Backend\Model\View\Result\Forward;

/**
 * Class Pay
 */
class Pay extends View implements HttpGetActionInterface
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::sales_invoice';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ForwardFactory $resultForwardFactory
     * @param Config $config
     * @param LoggerInterface $logger
     * @param InvoiceRepositoryInterface|null $invoiceRepository
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ForwardFactory $resultForwardFactory,
        Config $config,
        LoggerInterface $logger,
        InvoiceRepositoryInterface $invoiceRepository = null
    ) {
        parent::__construct($context, $registry, $resultForwardFactory, $invoiceRepository);
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Pay Invoice
     *
     * @return Forward|ResponseInterface
     */
    public function execute()
    {
        $invoice = $this->getInvoice();

        if (empty($invoice)) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $stripeInvoiceId = $invoice->getStripeInvoiceId();
        if (empty($stripeInvoiceId)) {
            $this->messageManager->addErrorMessage(__('This invoice for order cannot do paid. Invoice id is empty.'));
            return $this->redirectToBack($invoice);
        }

        $oldStatus = $invoice->getStripeInvoicePaid();
        if ($oldStatus == StripeInvoice::STRIPE_INVOICE_PAID) {
            $this->messageManager->addErrorMessage(__('Invoice is paid.'));
            return $this->redirectToBack($invoice);
        }

        try {
            $result = $this->config->getStripeClient()->invoices->pay(
                $stripeInvoiceId,
                ['forgive' => true, 'off_session' => false]
            );

            if (!empty($result) && $result->paid) {
                $invoice->setStripeInvoicePaid(StripeInvoice::STRIPE_INVOICE_PAID);
                $this->messageManager->addSuccessMessage(__('The invoice has been successfully paid.'));
            } else {
                $invoice->setStripeInvoicePaid(StripeInvoice::STRIPE_INVOICE_NOT_PAID_ERROR);
                $this->messageManager->addErrorMessage(__('The invoice was not paid.'));
            }

            if ($oldStatus != $invoice->getStripeInvoicePaid()) {
                $this->invoiceRepository->save($invoice);
            }
        } catch (Exception | LocalizedException $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->redirectToBack($invoice);
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    /**
     * Redirect To Back
     *
     * @param $invoice
     * @return ResponseInterface
     */
    private function redirectToBack($invoice): ResponseInterface
    {
        return $this->_redirect($this->getUrl(
            'sales/order_invoice/view/invoice_id/' . $invoice->getId()
        ));
    }
}

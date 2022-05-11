<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Retailplace\MiraklFrontendDemo\Model\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Sales\Model\Order\Pdf\InvoiceFactory;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Exception;

/**
 * Index class
 */
class GetPdf extends Action
{
    /**
     * @var OrderViewAuthorizationInterface
     */
    protected $orderAuthorization;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepositoryInterface;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var InvoiceFactory
     */
    private $invoiceFactory;

    /**
     * @param Context $context
     * @param OrderViewAuthorizationInterface $orderAuthorization
     * @param PageFactory $resultPageFactory
     * @param FileFactory $fileFactory
     * @param InvoiceFactory $invoiceFactory
     * @param InvoiceRepositoryInterface $invoiceRepositoryInterface
     * @param DateTimeFactory $dateTimeFactory
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        OrderViewAuthorizationInterface $orderAuthorization,
        PageFactory $resultPageFactory,
        FileFactory $fileFactory,
        InvoiceFactory $invoiceFactory,
        InvoiceRepositoryInterface $invoiceRepositoryInterface,
        DateTimeFactory $dateTimeFactory,
        Session $customerSession
    ) {
        $this->orderAuthorization = $orderAuthorization;
        $this->resultPageFactory = $resultPageFactory;
        $this->fileFactory = $fileFactory;
        $this->invoiceFactory = $invoiceFactory;
        $this->invoiceRepositoryInterface = $invoiceRepositoryInterface;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * Print Invoice Action
     *
     * @return Redirect
     * @throws Exception
     */
    public function execute(): Redirect
    {
        $invoiceId = (int) $this->getRequest()->getParam('invoice_id');

        if ($invoiceId) {
            $invoice = $this->invoiceRepositoryInterface->get($invoiceId);
            $order = $invoice->getOrder();

            if ($this->orderAuthorization->canView($order)) {
                $pdf = $this->invoiceFactory->create()->getPdf([$invoice]);
                $date = $this->dateTimeFactory->create()->date('Y-m-d_H-i-s');
                $fileContent = ['type' => 'string', 'value' => $pdf->render(), 'rm' => true];

                $this->fileFactory->create(
                    'invoice' . $date . '.pdf',
                    $fileContent,
                    DirectoryList::VAR_DIR,
                    'application/pdf'
                );
            }
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($this->customerSession->isLoggedIn()) {
            $resultRedirect->setPath('invoices/index/index');
        } else {
            $resultRedirect->setPath('sales/guest/form');
        }
        return $resultRedirect;
    }
}

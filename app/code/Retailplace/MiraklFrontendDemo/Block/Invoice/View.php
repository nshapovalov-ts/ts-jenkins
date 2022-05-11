<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Block\Invoice;

use Magento\Framework\View\Element\Template;
use Exception;
use DateTime;
use Retailplace\Stripe\Rewrite\Model\Method\Invoice;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Sales\Model\Order\Invoice as OrderInvoice;
use Magento\Store\Model\ScopeInterface;

/**
 * Class View
 */
class View extends Template
{
    /**
     * @type string
     */
    const XML_PATH_SHOW_INVOICES_FROM_DATE = 'tradesquare_invoices/invoices/from_date';

    /**
     * @var int
     */
    const PAGE_SIZE = 10;

    /**
     * @var int
     */
    const SCATTER_PAGES = 1;

    /**
     * @var int
     */
    const INVOICE_TYPE_CANCELED = 3;

    /**
     * @var string[]
     */
    private $statusType = [
        Invoice::STRIPE_INVOICE_NOT_PAID       => 'awaiting payment',
        Invoice::STRIPE_INVOICE_PAID           => 'paid',
        Invoice::STRIPE_INVOICE_NOT_PAID_ERROR => 'failed payment',
        self::INVOICE_TYPE_CANCELED            => 'canceled'
    ];

    /**
     * @var int[]
     */
    private $magentoStatusTypeMapping = [
        OrderInvoice::STATE_OPEN     => Invoice::STRIPE_INVOICE_NOT_PAID,
        OrderInvoice::STATE_PAID     => Invoice::STRIPE_INVOICE_PAID,
        OrderInvoice::STATE_CANCELED => self::INVOICE_TYPE_CANCELED
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var Data
     */
    private $priceHelper;

    /**
     * @var int
     */
    private $currentPage;

    /**
     * @var int
     */
    private $page;

    /**
     * @var float|int
     */
    private $pageCount;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Template\Context $context
     * @param LoggerInterface $logger
     * @param ObjectFactory $objectFactory
     * @param Session $customerSession
     * @param CollectionFactory $orderCollectionFactory
     * @param Data $priceHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        LoggerInterface $logger,
        ObjectFactory $objectFactory,
        Session $customerSession,
        CollectionFactory $orderCollectionFactory,
        Data $priceHelper,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->objectFactory = $objectFactory;
        $this->customerSession = $customerSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->priceHelper = $priceHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * Get Invoices
     *
     * @throws Exception
     */
    public function getInvoices(): array
    {
        $invoices = [];
        try {
            $customerId = $this->getCustomer()->getId();
            $collection = $this->orderCollectionFactory->create();
            $connection = $collection->getConnection();

            $collection->addFieldToFilter('customer_id', ['eq' => $customerId]);
            $collection->getSelect()->reset('columns');

            $collection->getSelect()->join(
                ['invoice' => $connection->getTableName('sales_invoice')],
                'invoice.order_id = ' . 'main_table.entity_id',
                [
                    'created_at'          => 'main_table.created_at',
                    'payment_date'        => 'main_table.payment_date',
                    'increment_id'        => 'main_table.increment_id',
                    'grand_total'         => 'main_table.grand_total',
                    'total_refunded'      => 'main_table.total_refunded',
                    'invoice_created_at'  => 'invoice.created_at',
                    'invoice_id'          => 'invoice.entity_id',
                    'stripe_invoice_paid' => 'invoice.stripe_invoice_paid',
                    'invoice_state'       => 'invoice.state'
                ]
            );

            $showInvoicesFromDate = $this->_scopeConfig->getValue(
                self::XML_PATH_SHOW_INVOICES_FROM_DATE,
                ScopeInterface::SCOPE_STORE
            );

            if (!empty($showInvoicesFromDate)) {
                $collection->getSelect()->where('invoice.created_at >= ?', $showInvoicesFromDate);
            }

            $collection->getSelect()->order('invoice.created_at DESC');

            $allInvoices = $collection->count();

            $page = $this->getRequest()->getParam('p');

            if (empty($page)) {
                $page = 1;
            }

            $this->pageCount = !empty($allInvoices) && $allInvoices > self::PAGE_SIZE ? (int) ceil($allInvoices / self::PAGE_SIZE) : 1;
            $this->currentPage = !empty($page) ? $page : 1;

            if ($this->pageCount < $this->currentPage) {
                $this->currentPage = $this->pageCount;
            }

            $collection->clear();
            $collection->setCurPage($this->currentPage);
            $collection->setPageSize(self::PAGE_SIZE);

            foreach ($collection as $item) {
                $date = $item->getPaymentDate() ?? $item->getCreatedAt();
                $invoiceDate = $item->getInvoiceCreatedAt();

                $type = $item->getStripeInvoicePaid();

                $status = array_key_exists($type, $this->statusType)
                    ? $this->statusType[$type] : "";

                $invoiceState = $item->getInvoiceState();
                if (empty($status) && !empty($invoiceState)
                    && array_key_exists($invoiceState, $this->magentoStatusTypeMapping)) {
                    if (array_key_exists($this->magentoStatusTypeMapping[$invoiceState], $this->statusType)) {
                        $type = $this->magentoStatusTypeMapping[$invoiceState];
                        $status = $this->statusType[$type];
                    } else {
                        $status = "";
                    }
                }

                $totalDue = 0;
                if ($type == Invoice::STRIPE_INVOICE_NOT_PAID) {
                    $totalDue = $item->getGrandTotal() - $item->getTotalRefunded();
                }

                $invoice = [
                    'invoice_url'  => $this->getUrl(
                        'invoices/index/getpdf',
                        ['invoice_id' => $item->getInvoiceId(), '_secure' => true]
                    ),
                    'order_url'    => "",//todo ?
                    'order_number' => $item->getIncrementId(),
                    'date'         => (new DateTime($invoiceDate)),
                    'status_type'  => $type,
                    'status'       => $status,
                    'order_amount' => $this->formatPrice($item->getGrandTotal()),
                    'amount_due'   => $this->formatPrice($totalDue),
                    'due_date'     => (new DateTime($date))
                ];

                $invoices[] = $this->objectFactory->create(['data' => $invoice]);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $invoices;
    }

    /**
     * Get Page Count
     *
     * @return int
     */
    public function getPageCount()
    {
        return $this->pageCount;
    }

    /**
     * Get Current Page
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        if (empty($this->currentPage)) {
            $this->currentPage = 1;
        }

        return (int) $this->currentPage;
    }

    /**
     * @param $price
     * @return float|string
     */
    public function formatPrice($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * Set Page
     *
     * @param int $page
     */
    public function setPage(int $page)
    {
        $this->page = $page;
    }

    /**
     * Get Page
     *
     * @return int|null
     */
    public function getPage(): ?int
    {
        return $this->page;
    }

    /**
     * Get Next Page
     *
     * @return string
     */
    public function getNextPage(): string
    {
        $url = "";
        if ($this->currentPage < $this->pageCount) {
            $nextPage = $this->currentPage + 1;
            $url = $this->getUrl(
                'invoices/index/index',
                ['p' => $nextPage, '_secure' => true]
            );
        }

        return $url;
    }

    /**
     * Get Previous Page
     *
     * @return string
     */
    public function getPreviousPage(): string
    {
        $url = "";
        if ($this->currentPage > 1) {
            $nextPage = $this->currentPage - 1;
            $url = $this->getUrl(
                'invoices/index/index',
                ['p' => $nextPage, '_secure' => true]
            );
        }

        return $url;
    }

    /**
     * Get Pages
     *
     * @return array
     */
    public function getPages(): array
    {
        $pages = [];

        if ($this->pageCount > 1) {
            $min = ($this->currentPage - self::SCATTER_PAGES) >= 1 ? $this->currentPage - self::SCATTER_PAGES : 1;
            $max = ($this->currentPage + self::SCATTER_PAGES) <= $this->pageCount
                ? $this->currentPage + self::SCATTER_PAGES : $this->pageCount;
            $range = range($min, $max);

            foreach ($range as $id) {
                $pages[$id] = $this->getUrl(
                    'invoices/index/index',
                    ['p' => $id, '_secure' => true]
                );
            }

            if ($min != 1) {
                $pages[1] = $this->getUrl(
                    'invoices/index/index',
                    ['p' => 1, '_secure' => true]
                );
                if ($min != 2) {
                    $pages[2] = '#';
                }
            }

            if ($max != $this->pageCount) {
                if ($max != $this->pageCount - 1) {
                    $pages[$this->pageCount - 1] = '#';
                }

                $pages[$this->pageCount] = $this->getUrl(
                    'invoices/index/index',
                    ['p' => $this->pageCount, '_secure' => true]
                );

                $pages = $this->validatePages($pages, $this->currentPage);
            }
        }
        ksort($pages);
        return $pages;
    }

    /**
     * Validate Pages
     *
     * @param $pages
     * @param $currentPage
     * @return mixed
     */
    private function validatePages($pages, $currentPage)
    {
        if (count($pages) > 5) {
            if (!empty($pages[$currentPage - 1])) {
                unset($pages[$currentPage - 1]);
            } else {
                if (!empty($pages[$currentPage + 1])) {
                    unset($pages[$currentPage + 1]);
                } else {
                    return $pages;
                }
            }
            return $this->validatePages($pages, $currentPage);
        }

        return $pages;
    }
}

<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Model;

use InvalidArgumentException;
use Mirakl\Core\Domain\Document;
use Mirakl\Api\Helper\Order as MiraklApi;
use Mirakl\MMP\Shop\Request\Order\Document\DownloadOrdersDocumentsRequestFactory;
use Mirakl\MMP\Shop\Request\Order\Document\GetOrderDocumentsRequestFactory;

/**
 * Class MiraklOrderInfo provide additional info for mirakl orders from API
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class MiraklOrderInfo
{
    /** @var string */
    public const SHIPPING_INVOICE_FIELD_NAME = 'sp_in';
    public const ORDER_ID_PARAM_NAME = 'order_id';
    public const MIRAKL_ACTUAL_SHIPPING_AMOUNT_FIELD = 'actual-shipping-amount';
    public const ACTUAL_SHIPPING_AMOUNT_FIELD = 'actual_shipping_amount';

    /** @var MiraklApi */
    private $miraklApi;

    /** @var DownloadOrdersDocumentsRequestFactory */
    private $downloadOrdersDocumentRequestFactory;

    /** @var GetOrderDocumentsRequestFactory */
    private $getOrderDocumentsRequestFactory;

    /**
     * @param MiraklApi $miraklApi
     * @param DownloadOrdersDocumentsRequestFactory $downloadOrdersDocumentRequestFactory
     * @param GetOrderDocumentsRequestFactory $getOrderDocumentsRequestFactory
     */
    public function __construct(
        MiraklApi $miraklApi,
        DownloadOrdersDocumentsRequestFactory $downloadOrdersDocumentRequestFactory,
        GetOrderDocumentsRequestFactory $getOrderDocumentsRequestFactory
    ) {
        $this->miraklApi = $miraklApi;
        $this->downloadOrdersDocumentRequestFactory = $downloadOrdersDocumentRequestFactory;
        $this->getOrderDocumentsRequestFactory = $getOrderDocumentsRequestFactory;
    }

    /**
     * @param string $orderId
     *
     * @return void
     * @throws  InvalidArgumentException
     */
    public function downloadShippingInvoice(string $orderId)
    {
        $downloadOrderRequest = $this->downloadOrdersDocumentRequestFactory->create();
        $downloadOrderRequest->setOrderIds([$orderId]);
        $downloadOrderRequest->setDocumentCodes([self::SHIPPING_INVOICE_FIELD_NAME]);
        /** @var Document $document */
        $document = $this->miraklApi->send($downloadOrderRequest);
        $document->download();
    }

    /**
     * @param string $orderId
     * @return bool
     */
    public function isActualShippingInvoiceUploaded(string $orderId): bool
    {
        $status = false;
        $getOrderDocumentsRequest = $this->getOrderDocumentsRequestFactory->create(['orderIds' => [$orderId]]);
        $documents = $this->miraklApi->send($getOrderDocumentsRequest);
        foreach ($documents->getItems() as $item) {
            if ($item->getTypeCode() == self::SHIPPING_INVOICE_FIELD_NAME) {
                $status = true;
                break;
            }
        }

        return $status;
    }
}

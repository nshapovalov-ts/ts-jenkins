<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Model;

use GuzzleHttp\Exception\ConnectException;
use Mirakl\MMP\Shop\Request\Order\Get\GetOrdersRequestFactory;
use Mirakl\MMP\Shop\Request\Order\Document\GetOrderDocumentsRequestFactory;
use Mirakl\Api\Helper\Order as MiraklApi;
use Magento\Framework\App\ResourceConnection;

/**
 * Class MiraklOrder
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class MiraklOrderUpdater
{

    /** @var int */
    public const DOCUMENT_API_CALL_CHUNK = 100;

    /** @var string */
    public const IMPORT_ENTITY_TYPE = 'orders';
    public const IMPORT_ENTITY_TABLE = 'mirakl_order';

    /** @var GetOrdersRequestFactory */
    private $getOrderRequestFactory;

    /** @var GetOrderDocumentsRequestFactory */
    private $getOrderDocumentsRequestFactory;

    /** @var MiraklApi */
    private $miraklApi;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var array */
    private $miraklOrders = [];

    /**
     * @param GetOrdersRequestFactory $getOrderRequestFactory
     * @param GetOrderDocumentsRequestFactory $getOrderDocumentsRequestFactory
     * @param ResourceConnection $resourceConnection
     * @param ConfigProvider $configProvider
     * @param MiraklApi $miraklApi
     */
    public function __construct(
        GetOrdersRequestFactory $getOrderRequestFactory,
        GetOrderDocumentsRequestFactory $getOrderDocumentsRequestFactory,
        ResourceConnection $resourceConnection,
        ConfigProvider $configProvider,
        MiraklApi $miraklApi
    ) {
        $this->getOrderRequestFactory = $getOrderRequestFactory;
        $this->getOrderDocumentsRequestFactory = $getOrderDocumentsRequestFactory;
        $this->resourceConnection = $resourceConnection;
        $this->configProvider = $configProvider;
        $this->miraklApi = $miraklApi;
    }

    /**
     * @return void
     * @throws ConnectException
     */
    public function update()
    {
        if ($this->configProvider->isMiraklOrderSyncEnable()) {
            $syncDate = $this->configProvider->getSyncDate(self::IMPORT_ENTITY_TYPE);
            $getOrderRequest = $this->getOrderRequestFactory->create();
            $getOrderRequest->setPaginate(false);
            if ($syncDate) {
                $getOrderRequest->setStartUpdateDate($syncDate);
            }
            $apiResponse = $this->miraklApi->send($getOrderRequest);
            $orders = $apiResponse->getItems();
            if (!empty($orders)) {
                foreach ($orders as $orderItem) {
                    $orderData = $orderItem->toArray();
                    $this->miraklOrders[$orderData['id']] = $this->getOrderArray($orderData);
                }
                $this->setOrderAdditionalData();
                $this->resourceConnection->getConnection()->insertOnDuplicate(
                    $this->resourceConnection->getTableName(self::IMPORT_ENTITY_TABLE),
                    $this->miraklOrders
                );
                $this->configProvider->setSyncDate(self::IMPORT_ENTITY_TYPE);
            }
        }
    }

    /**
     * @return void
     */
    private function setOrderAdditionalData()
    {
        $orderIds = array_keys($this->miraklOrders);
        foreach (array_chunk($orderIds, self::DOCUMENT_API_CALL_CHUNK) as $orderIdsChunk) {
            $getOrderDocumentsRequest = $this->getOrderDocumentsRequestFactory->create(['orderIds' => $orderIdsChunk]);
            $apiResponse = $this->miraklApi->send($getOrderDocumentsRequest);
            foreach ($apiResponse->getItems() as $item) {
                $orderDocumentsData = $item->toArray();
                $orderId = $orderDocumentsData['order_id'];
                if ($orderDocumentsData['type_code'] == 'sp_in') {
                    $this->miraklOrders[$orderId]['actual_shipping_uploaded'] = true;
                }
            }
        }
    }

    /**
     * @param array $orderData
     * @return array
     */
    private function getOrderArray($orderData): array
    {
        $order['mirakl_order_id'] = $orderData['id'];
        $order['order_increment_id'] = $orderData['commercial_id'];
        $order['mirakl_shop_id'] = $orderData['shop_id'];
        $order['mirakl_shop_name'] = $orderData['shop_name'];
        $order['mirakl_order_status'] = $orderData['status']['state'];
        $order['order_lines'] = count($orderData['order_lines']);
        $order['has_invoice'] = $orderData['has_invoice'];
        $order['has_incident'] = $orderData['has_incident'];
        $order['total_commission'] = $orderData['total_commission'];
        $order['total_price'] = $orderData['total_price'];
        $order['actual_shipping_uploaded'] = false;
        $order['actual_shipping_amount'] = $this->getActualShippingAmount($orderData);

        return $order;
    }

    /**
     * @param $orderData
     * @return float
     */
    private function getActualShippingAmount($orderData): float
    {
        $shippingAmount = 0.0;
        foreach ($orderData['order_additional_fields'] as $additionalField) {
            if ($additionalField['code'] == 'actual-shipping-amount') {
                $shippingAmount = (float)$additionalField['value'];
            }
        }

        return $shippingAmount;
    }
}

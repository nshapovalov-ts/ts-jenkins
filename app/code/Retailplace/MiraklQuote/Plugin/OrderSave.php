<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Plugin;

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mirakl\Connector\Helper\Config as ConfigHelper;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Retailplace\MiraklQuote\Api\Data\MiraklQuoteAttributes;
use Retailplace\MiraklOrder\Model\MiraklOrderManagement;
use Retailplace\MiraklQuote\Model\MiraklQuoteManagement;
use Psr\Log\LoggerInterface;

/**
 * Class OrderSave
 */
class OrderSave
{
    /** @var \Mirakl\Connector\Helper\Order */
    private $orderHelper;

    /** @var \Mirakl\Connector\Helper\Config */
    private $configHelper;

    /** @var \Retailplace\MiraklQuote\Model\MiraklQuoteManagement */
    private $miraklQuoteManagement;

    /** @var \Retailplace\MiraklOrder\Model\MiraklOrderManagement */
    private $miraklOrderManagement;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param \Mirakl\Connector\Helper\Order $orderHelper
     * @param \Mirakl\Connector\Helper\Config $configHelper
     * @param \Retailplace\MiraklQuote\Model\MiraklQuoteManagement $miraklQuoteManagement
     * @param \Retailplace\MiraklOrder\Model\MiraklOrderManagement $miraklOrderManagement
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        OrderHelper $orderHelper,
        ConfigHelper $configHelper,
        MiraklQuoteManagement $miraklQuoteManagement,
        MiraklOrderManagement $miraklOrderManagement,
        LoggerInterface $logger
    ) {
        $this->orderHelper = $orderHelper;
        $this->configHelper = $configHelper;
        $this->miraklQuoteManagement = $miraklQuoteManagement;
        $this->miraklOrderManagement = $miraklOrderManagement;
        $this->logger = $logger;
    }

    /**
     * Send new Order to Mirakl
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order $order
     * @return \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order
     */
    public function afterSave(OrderRepositoryInterface $subject, OrderInterface $order): OrderInterface
    {
        $validStatus = in_array($order->getStatus(), $this->configHelper->getCreateOrderStatuses());
        $alreadySent = $order->getData('mirakl_sent');

        if ($order->getData(MiraklQuoteAttributes::MIRAKL_ORDER_QUOTE_ID)) {
            if ($this->configHelper->isAutoCreateOrder()) {
                if ($validStatus && !$alreadySent && $this->orderHelper->isMiraklOrder($order)) {
                    $miraklOrders = $this->miraklQuoteManagement->createMiraklOrder($order);
                    $isOrderAccepted = $this->miraklOrderManagement->acceptOrders($miraklOrders);
                    if ($isOrderAccepted) {
                        $this->miraklOrderManagement->debitPayment($miraklOrders);
                    }
                    $this->miraklOrderManagement->saveMiraklResponseOrderCollection($miraklOrders);
                }
            }
        } else {
            if ($this->configHelper->isAutoCreateOrder()) {
                if ($validStatus && !$alreadySent && $this->orderHelper->isMiraklOrder($order)) {
                    try {
                        $this->orderHelper->createMiraklOrder($order);
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            }
        }

        return $order;
    }
}

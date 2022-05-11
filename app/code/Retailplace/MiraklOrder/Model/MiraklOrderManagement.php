<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Model;

use Exception;
use Mirakl\Api\Helper\ClientHelper\MMP;
use Mirakl\Api\Helper\Payment as MiraklPaymentHelper;
use Mirakl\MMP\FrontOperator\Domain\Collection\Order\OrderCollection;
use Mirakl\MMP\FrontOperator\Domain\Order;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklOrder\Model\MiraklOrderFactory;
use Retailplace\MiraklOrder\Api\MiraklOrderRepositoryInterface;
use Retailplace\MiraklQuote\Model\OrderRequestBuilder;
use Retailplace\SellerAffiliate\Model\SellerAffiliateManagement;
use Mirakl\MMP\Common\Domain\Order\OrderState;

/**
 * Class MiraklOrderManagement
 */
class MiraklOrderManagement
{
    /** @var \Retailplace\MiraklOrder\Model\MiraklOrderFactory */
    private $miraklOrderFactory;

    /** @var \Retailplace\MiraklOrder\Api\MiraklOrderRepositoryInterface */
    private $miraklOrderRepository;

    /** @var \Retailplace\SellerAffiliate\Model\SellerAffiliateManagement */
    private $sellerAffiliateManagement;

    /** @var \Retailplace\MiraklQuote\Model\OrderRequestBuilder */
    private $orderRequestBuilder;

    /** @var \Mirakl\Api\Helper\ClientHelper\MMP */
    private $miraklApiClient;

    /** @var \Mirakl\Api\Helper\Payment */
    private $miraklPaymentHelper;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param \Retailplace\MiraklOrder\Model\MiraklOrderFactory $miraklOrderFactory
     * @param \Retailplace\MiraklOrder\Api\MiraklOrderRepositoryInterface $miraklOrderRepository
     * @param \Retailplace\SellerAffiliate\Model\SellerAffiliateManagement $sellerAffiliateManagement
     * @param \Retailplace\MiraklQuote\Model\OrderRequestBuilder $orderRequestBuilder
     * @param \Mirakl\Api\Helper\ClientHelper\MMP $miraklApiClient
     * @param \Mirakl\Api\Helper\Payment $miraklPaymentHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        MiraklOrderFactory $miraklOrderFactory,
        MiraklOrderRepositoryInterface $miraklOrderRepository,
        SellerAffiliateManagement $sellerAffiliateManagement,
        OrderRequestBuilder $orderRequestBuilder,
        MMP $miraklApiClient,
        MiraklPaymentHelper $miraklPaymentHelper,
        LoggerInterface $logger
    ) {
        $this->miraklOrderFactory = $miraklOrderFactory;
        $this->miraklOrderRepository = $miraklOrderRepository;
        $this->sellerAffiliateManagement = $sellerAffiliateManagement;
        $this->orderRequestBuilder = $orderRequestBuilder;
        $this->miraklApiClient = $miraklApiClient;
        $this->miraklPaymentHelper = $miraklPaymentHelper;
        $this->logger = $logger;
    }

    /**
     * Save Order Creation Response Collection from Mirakl
     *
     * @param \Mirakl\MMP\FrontOperator\Domain\Collection\Order\OrderCollection $miraklResponseOrderCollection
     */
    public function saveMiraklResponseOrderCollection(OrderCollection $miraklResponseOrderCollection)
    {
        foreach ($miraklResponseOrderCollection->getItems() as $miraklResponseOrder) {
            $this->saveMiraklResponseOrder($miraklResponseOrder);
        }
    }

    /**
     * Save Order Creation Response Object from Mirakl
     *
     * @param \Mirakl\MMP\FrontOperator\Domain\Order $miraklResponseOrder
     */
    public function saveMiraklResponseOrder(Order $miraklResponseOrder)
    {
        try {
            $miraklOrder = $this->miraklOrderRepository->getByMiraklOrderId($miraklResponseOrder->getId());
        } catch (Exception $e) {
            $miraklOrder = $this->miraklOrderFactory->create();
        }

        $miraklOrder->setMiraklOrderId($miraklResponseOrder->getId());
        $miraklOrder->setIsAffiliated($this->sellerAffiliateManagement->isCustomerAffiliated(
            $this->getCustomerId($miraklResponseOrder),
            (int) $miraklResponseOrder->getShopId()
        ));

        try {
            $this->miraklOrderRepository->save($miraklOrder);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Accept Mirakl Order
     *
     * @param \Mirakl\MMP\FrontOperator\Domain\Collection\Order\OrderCollection|null $miraklOrderCollection
     * @return bool
     */
    public function acceptOrders(?OrderCollection $miraklOrderCollection): bool
    {
        $response = true;
        if ($miraklOrderCollection) {
            /** @var \Mirakl\MMP\FrontOperator\Domain\Order $miraklOrder */
            foreach ($miraklOrderCollection->getItems() as $miraklOrder) {
                $acceptOrderRequest = $this->orderRequestBuilder->getAcceptOrderRequest(
                    $miraklOrder->getId(),
                    $miraklOrder->getOrderLines()
                );
                try {
                    $sendResponse = $this->miraklApiClient->send($acceptOrderRequest);
                    if (!$sendResponse) {
                        $response = false;
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        return $response;
    }

    /**
     * Debit Payment for Mirakl Order
     *
     * @param \Mirakl\MMP\FrontOperator\Domain\Collection\Order\OrderCollection $miraklOrders
     */
    public function debitPayment(OrderCollection $miraklOrders)
    {
        /** @var \Mirakl\MMP\FrontOperator\Domain\Order $miraklOrder */
        foreach ($miraklOrders as $miraklOrder) {
            $miraklOrder->getStatus()->setState(OrderState::WAITING_DEBIT);
            try {
                $this->miraklPaymentHelper->debitPayment($miraklOrder, $miraklOrder->getCustomer()->getId());
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
     * Get Customer ID from Mirakl Order Response
     *
     * @param \Mirakl\MMP\FrontOperator\Domain\Order $miraklResponseOrder
     * @return int
     */
    private function getCustomerId(Order $miraklResponseOrder): int
    {
        $customerId = 0;
        $customer = $miraklResponseOrder->getCustomer();
        if ($customer) {
            $customerId = (int) $customer->getId();
        }

        return $customerId;
    }
}

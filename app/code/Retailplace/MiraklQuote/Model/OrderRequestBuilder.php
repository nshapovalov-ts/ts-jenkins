<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Model;

use ArrayIterator;
use Mirakl\MMP\Common\Domain\Collection\Order\Accept\AcceptOrderLineCollection;
use Mirakl\MMP\Front\Request\Order\Accept\AcceptOrderRequestFactory;
use Mirakl\MMP\Front\Request\Order\Accept\AcceptOrderRequest;
use Mirakl\MMP\Common\Domain\Collection\Order\Accept\AcceptOrderLineCollectionFactory;
use Mirakl\MMP\Common\Domain\Order\Accept\AcceptOrderLineFactory;

/**
 * Class OrderRequestBuilder
 */
class OrderRequestBuilder
{
    /** @var \Mirakl\MMP\Front\Request\Order\Accept\AcceptOrderRequestFactory */
    private $acceptOrderRequestFactory;

    /** @var \Mirakl\MMP\Common\Domain\Collection\Order\Accept\AcceptOrderLineCollectionFactory */
    private $acceptOrderLineCollectionFactory;

    /** @var \Mirakl\MMP\Common\Domain\Order\Accept\AcceptOrderLineFactory */
    private $acceptOrderLineFactory;

    /**
     * Constructor
     *
     * @param \Mirakl\MMP\Front\Request\Order\Accept\AcceptOrderRequestFactory $acceptOrderRequestFactory
     * @param \Mirakl\MMP\Common\Domain\Collection\Order\Accept\AcceptOrderLineCollectionFactory $acceptOrderLineCollectionFactory
     * @param \Mirakl\MMP\Common\Domain\Order\Accept\AcceptOrderLineFactory $acceptOrderLineFactory
     */
    public function __construct(
        AcceptOrderRequestFactory $acceptOrderRequestFactory,
        AcceptOrderLineCollectionFactory $acceptOrderLineCollectionFactory,
        AcceptOrderLineFactory $acceptOrderLineFactory
    ) {
       $this->acceptOrderRequestFactory = $acceptOrderRequestFactory;
       $this->acceptOrderLineCollectionFactory = $acceptOrderLineCollectionFactory;
       $this->acceptOrderLineFactory = $acceptOrderLineFactory;
    }

    /**
     * Get Accept Order Request
     *
     * @param string $miraklOrderId
     * @param array|\Mirakl\MMP\FrontOperator\Domain\Collection\Order\OrderLineCollection $orderLines
     * @return \Mirakl\MMP\Front\Request\Order\Accept\AcceptOrderRequest
     */
    public function getAcceptOrderRequest(string $miraklOrderId, $orderLines): AcceptOrderRequest
    {
        /** @var \Mirakl\MMP\Front\Request\Order\Accept\AcceptOrderRequest $acceptOrderRequest */
        $acceptOrderRequest = $this->acceptOrderRequestFactory->create([
            'orderId' => $miraklOrderId,
            'orderLines' => $this->getAcceptOrderLinesCollection($orderLines)
        ]);

        return $acceptOrderRequest;
    }

    /**
     * Get Accept Order Lines Collection
     *
     * @param array|\Mirakl\MMP\FrontOperator\Domain\Collection\Order\OrderLineCollection $orderLines
     * @return \Mirakl\MMP\Common\Domain\Collection\Order\Accept\AcceptOrderLineCollection
     */
    private function getAcceptOrderLinesCollection($orderLines): AcceptOrderLineCollection
    {
        /** @var \Mirakl\MMP\Common\Domain\Collection\Order\Accept\AcceptOrderLineCollection $orderLinesCollection */
        $orderLinesCollection = $this->acceptOrderLineCollectionFactory->create();
        foreach ($orderLines as $orderLine) {
            /** @var \Mirakl\MMP\Common\Domain\Order\Accept\AcceptOrderLine $acceptOrderLine */
            $acceptOrderLine = $this->acceptOrderLineFactory->create();
            $acceptOrderLine->setAccepted(true);
            $acceptOrderLine->setId($orderLine->getId());
            $orderLinesCollection->add($acceptOrderLine);
        }

        return $orderLinesCollection;
    }


}

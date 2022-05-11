<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Model\CollectionProcessor\JoinProcessor;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinDataInterfaceFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\JoinProcessor\CustomJoinInterface;

class OrderInvoice implements CustomJoinInterface
{
    /**
     * @var JoinDataInterfaceFactory
     */
    private $joinDataFactory;

    /**
     * @var JoinProcessorInterface
     */
    private $joinProcessor;

    /**
     * OrderInvoice constructor.
     *
     * @param JoinDataInterfaceFactory $joinDataFactory
     * @param JoinProcessorInterface $joinProcessor
     */
    public function __construct(
        JoinDataInterfaceFactory $joinDataFactory,
        JoinProcessorInterface $joinProcessor
    ) {
        $this->joinDataFactory = $joinDataFactory;
        $this->joinProcessor = $joinProcessor;
    }

    /**
     * @inheritDoc
     */
    public function apply(AbstractDb $collection)
    {
        $joinDataInvoice = $this->joinDataFactory->create();
        $joinDataInvoice->setJoinField(OrderInterface::ENTITY_ID)
            ->setReferenceTable('sales_invoice')
            ->setReferenceField('order_id')
            ->setReferenceTableAlias('invoice')
            ->setSelectFields([]);

        $collection->joinExtensionAttribute($joinDataInvoice, $this->joinProcessor);
    }
}

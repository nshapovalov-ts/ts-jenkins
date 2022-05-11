<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

namespace Retailplace\MiraklSeller\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order as OrderModel;
use MiraklSeller\Sales\Helper\CreditMemo as MiraklCreditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditMemoCollectionFactory;
use Zend_Db_ExprFactory;

/**
 * Class Creditmemo
 */
class Creditmemo extends MiraklCreditmemo
{
    /** @var \Zend_Db_ExprFactory  */
    private $exprFactory;

    /**
     * Creditmemo constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $creditMemoCollectionFactory
     * @param \Zend_Db_ExprFactory $exprFactory
     */
    public function __construct(
        Context $context,
        CreditMemoCollectionFactory $creditMemoCollectionFactory,
        Zend_Db_ExprFactory $exprFactory
    ) {
        parent::__construct($context, $creditMemoCollectionFactory);
        $this->exprFactory = $exprFactory;
    }

    /**
     * Overwritten method
     *
     * @param   int $miraklRefundId
     * @return  OrderModel\Creditmemo
     */
    public function getCreditMemoByMiraklRefundId($miraklRefundId)
    {
        $expr = $this->exprFactory->create(['expression' => "FIND_IN_SET ($miraklRefundId, `mirakl_refund_ids`)"]);
        /** @var OrderModel\Creditmemo $creditMemo */
        $creditmemoCollection = $this->creditMemoCollectionFactory->create();
        $creditmemoCollection->getSelect()->where($expr);

        return $creditmemoCollection->getFirstItem();
    }
}

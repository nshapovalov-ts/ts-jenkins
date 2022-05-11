<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class UpdateMiraklRefundIds
 */
class UpdateMiraklRefundIds implements DataPatchInterface
{
    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var \Zend_Db_ExprFactory */
    private $exprFactory;

    /**
     * UpdateMiraklRefundIds constructor.
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Zend_Db_ExprFactory $exprFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Zend_Db_ExprFactory $exprFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->exprFactory = $exprFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $creditmemoTable = $this->moduleDataSetup->getTable('sales_creditmemo');

        $expr = $this->exprFactory->create(['expression' => 'mirakl_refund_id']);

        $this->moduleDataSetup->getConnection()->update(
            $creditmemoTable,
            ['mirakl_refund_ids' => $expr],
            $this->exprFactory->create(['expression' => 'mirakl_refund_ids IS NULL'])
        );
    }
}

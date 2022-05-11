<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Model\ResourceModel;

use Mirakl\Process\Model\Process;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory;
use Mirakl\MMP\FrontOperator\Domain\Collection\Shop\ShopCollection;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;

/**
 * Class Shop
 */
class Shop extends \Mirakl\Core\Model\ResourceModel\Shop
{
    /** @var string */
    public const TABLE_NAME = 'mirakl_shop';

    /**
     * Constructor
     *
     * @param \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param $connectionName
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        AttributeRepository $attributeRepository,
        Context $context,
        $connectionName = null
    ) {
        parent::__construct(
            $collectionFactory,
            $attributeRepository,
            $context,
            $connectionName
        );
    }

    /**
     * Mirakl Seller Sync
     * Method is disabled, the synchronize call moved out from resource model
     * A plugin \Retailplace\MiraklConnector\Plugin\Helper\Shop::aroundSynchronize will call
     * sync method in \Retailplace\MiraklShop\Model\Synchronizer\ShopUpdater::synchronize
     *
     * @param \Mirakl\MMP\FrontOperator\Domain\Collection\Shop\ShopCollection $shops
     * @param \Mirakl\Process\Model\Process $process
     * @param int $chunkSize
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function synchronize(ShopCollection $shops, Process $process, $chunkSize = 100)
    {
        return 0;
    }
}

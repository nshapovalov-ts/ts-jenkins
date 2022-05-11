<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model\ResourceModel\Promotion;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Retailplace\MiraklPromotion\Model\Promotion as PromotionModel;
use Retailplace\MiraklPromotion\Model\ResourceModel\Promotion as PromotionResouceModel;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /** @var string */
    protected $_idFieldName = PromotionModel::ENTITY_ID;

    /** @var string */
    protected $_eventPrefix = 'retailplace_mirakl_promotion_collection';

    /** @var string */
    protected $_eventObject = 'promotion_collection';

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(PromotionModel::class, PromotionResouceModel::class);
    }
}

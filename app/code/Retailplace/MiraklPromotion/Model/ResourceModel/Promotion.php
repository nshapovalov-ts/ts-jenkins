<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Retailplace\MiraklPromotion\Model\Promotion as PromotionModel;

/**
 * Class Promotion
 */
class Promotion extends AbstractDb
{
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init(PromotionModel::TABLE_NAME, PromotionModel::ENTITY_ID);
    }
}

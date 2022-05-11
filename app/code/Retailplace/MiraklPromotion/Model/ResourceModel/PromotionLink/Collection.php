<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Retailplace\MiraklPromotion\Model\PromotionLink as PromotionLinkModel;
use Retailplace\MiraklPromotion\Model\Promotion as PromotionModel;
use Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink as PromotionLinkResouceModel;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /** @var string */
    protected $_idFieldName = PromotionLinkModel::ENTITY_ID;

    /** @var string */
    protected $_eventPrefix = 'retailplace_mirakl_promotion_link_collection';

    /** @var string */
    protected $_eventObject = 'promotion_link_collection';

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(PromotionLinkModel::class, PromotionLinkResouceModel::class);
    }

    /**
     * Join Offers Table
     */
    public function joinOffers()
    {
        $this->getSelect()
            ->joinInner(
                ['of' => $this->getTable('mirakl_offer')],
                'of.offer_id = main_table.offer_id'
            );

        $this->addFilterToMap('offer_id', 'main_table.offer_id');
    }

    /**
     * Join Promotions Table
     */
    public function joinPromotions()
    {
        $this->getSelect()
            ->joinInner(
                ['pr' => $this->getTable(PromotionModel::TABLE_NAME)],
                'pr.promotion_id = main_table.promotion_id'
            );

        $this->addFilterToMap('promotion_id', 'main_table.promotion_id');
    }
}

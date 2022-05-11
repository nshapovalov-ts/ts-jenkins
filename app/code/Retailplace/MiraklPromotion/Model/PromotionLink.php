<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model;

use Magento\Framework\Model\AbstractModel;
use Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink as PromotionLinkResourceModel;

/**
 * Class PromotionLink
 */
class PromotionLink extends AbstractModel
{
    /** @var string */
    public const TABLE_NAME = 'mirakl_promotion_link';
    public const ENTITY_ID = 'link_id';

    /** @var string */
    public const CACHE_TAG = 'retailplace_mirakl_promotion_link';

    /** @var string */
    protected $_cacheTag = 'retailplace_mirakl_promotion_link';

    /** @var string */
    protected $_eventPrefix = 'retailplace_mirakl_promotion_link';

    /**
     * Init model
     */
    protected function _construct()
    {
        $this->_init(PromotionLinkResourceModel::class);
    }
}

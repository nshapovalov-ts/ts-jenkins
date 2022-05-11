<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Retailplace\MiraklPromotion\Model\MiraklApi\Sync as PromotionSync;
use Retailplace\MiraklPromotion\Model\MiraklApi\AssociationSync as PromotionAssociationsSync;

/**
 * Class Import
 */
class Import
{
    /** @var string */
    public const XML_PATH_PROMOTIONS_SYNC_CRON_ENABLED = 'retailplace_mirakl_promotion/cron_settings/promotions_sync_enabled';
    public const XML_PATH_PROMOTIONS_SYNC_CRON_SCHEDULE = 'retailplace_mirakl_promotion/cron_settings/promotions_sync_schedule';
    public const XML_PATH_PROMOTIONS_LINK_SYNC_CRON_ENABLED = 'retailplace_mirakl_promotion/cron_settings/promotions_link_sync_schedule';
    public const XML_PATH_PROMOTIONS_LINK_SYNC_CRON_SCHEDULE = 'retailplace_mirakl_promotion/cron_settings/promotions_link_sync_schedule';

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Retailplace\MiraklPromotion\Model\MiraklApi\Sync */
    private $promotionSync;

    /** @var \Retailplace\MiraklPromotion\Model\MiraklApi\AssociationSync */
    private $promotionAssociationsSync;

    /**
     * Import Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Retailplace\MiraklPromotion\Model\MiraklApi\Sync $promotionSync
     * @param \Retailplace\MiraklPromotion\Model\MiraklApi\AssociationSync $promotionAssociationsSync
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PromotionSync $promotionSync,
        PromotionAssociationsSync $promotionAssociationsSync
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->promotionSync = $promotionSync;
        $this->promotionAssociationsSync = $promotionAssociationsSync;
    }

    /**
     * Run Promotions Import
     */
    public function runPromotionsImport()
    {
        if ($this->scopeConfig->isSetFlag(self::XML_PATH_PROMOTIONS_SYNC_CRON_ENABLED)) {
            $this->promotionSync->updatePromotions();
        }
    }

    /**
     * Run Promotions Associations Import
     */
    public function runPromotionsAssociationsImport()
    {
        if ($this->scopeConfig->isSetFlag(self::XML_PATH_PROMOTIONS_LINK_SYNC_CRON_ENABLED)) {
            $this->promotionAssociationsSync->updateAssociations();
        }
    }
}

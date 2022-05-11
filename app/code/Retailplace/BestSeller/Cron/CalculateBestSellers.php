<?php

/**
 * Retailplace_BestSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\BestSeller\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Retailplace\BestSeller\Model\BestSellersCalculatedManagement;

/**
 * Class CalculateBestSellers
 */
class CalculateBestSellers
{
    /** @var string */
    public const XML_PATH_BEST_SELLERS_CALCULATED_CRON_ENABLED = 'retailplace_best_sellers/cron_settings/best_sellers_update_enabled';

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Retailplace\BestSeller\Model\BestSellersCalculatedManagement  */
    private $bestSellerManagement;

    /**
     * CalculateBestSellers Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Retailplace\BestSeller\Model\BestSellersCalculatedManagement $bestSellerManagement
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        BestSellersCalculatedManagement $bestSellerManagement
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->bestSellerManagement = $bestSellerManagement;
    }

    /**
     * Run best seller update
     */
    public function execute()
    {
        if ($this->scopeConfig->isSetFlag(self::XML_PATH_BEST_SELLERS_CALCULATED_CRON_ENABLED)) {
            $this->bestSellerManagement->updateBestSellers();
        }
    }
}

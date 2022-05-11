<?php
declare(strict_types=1);
/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

namespace Retailplace\MiraklSeller\Model;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\HttpRequestInterface;
use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Retailplace\MiraklSeller\Controller\Index\Index;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class IsNewSeller
 */
class IsNewSeller
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;


    /**
     * @param TimezoneInterface $localeDate
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get days limit to label shops as new
     *
     * @return mixed
     */
    public function getNewLabelDaysCount()
    {
        return $this->scopeConfig->getValue(ShopInterface::XML_PATH_NB_DAYS_TO_LABEL_NEW);
    }

    /**
     * Get new label text for a shop
     *
     * @return string
     */
    public function getIsNewShopLabel(): string
    {
        return __('New on TradeSquare')->render();
    }
}

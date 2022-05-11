<?php

/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Plugin;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Framework\Search\Request\Builder;
use Retailplace\MiraklSellerAdditionalField\Helper\Data;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class SearchRequestBuilder
 */
class SearchRequestBuilder
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Data $helper
     * @param Http $request
     * @param Registry $coreRegistry
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Data $helper,
        Http $request,
        Registry $coreRegistry,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->helper = $helper;
        $this->request = $request;
        $this->coreRegistry = $coreRegistry;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Builder $subject
     * @return null
     */
    public function beforeCreate(
        Builder $subject
    ) {
        $filterIds = $this->request->getParam('filter_ids');
        if (!empty($filterIds)) {
            $subject->bind('ids', $filterIds);
        }

        $isSalableEnabled = $this->scopeConfig->isSetFlag(
            'amshopby/am_is_salable_filter/enabled',
            ScopeInterface::SCOPE_STORE
        );

        if ($isSalableEnabled) {
            $subject->bind("am_is_salable", 1);
        }

        return null;
    }
}

<?php
/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Block\Shop;

use Magento\Catalog\Block\Product\Context;
use Retailplace\MiraklSeller\Helper\Seller as SellerHelper;
use Magento\Framework\View\Element\Template;

/**
 * Class Report
 */
class Report extends Template
{
    /**
     * @var string
     */
    const TARGET_HTML_CONTAINER_ID = '#reportSeller';

    /**
     * @var SellerHelper
     */
    private $helper;

    /**
     * Report constructor
     *
     * @param Context $context
     * @param SellerHelper $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        SellerHelper $helper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
        $this->helper = $helper;
    }

    /**
     * Is Enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->helper->isEnableSellerNotifyForm();
    }

    /**
     * Get Config
     *
     * @return array|null
     */
    public function getConfig(): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $portalId = $this->helper->getSellerNotifyFormPortalId();
        $formId = $this->helper->getSellerNotifyFormId();

        if (!$portalId || !$formId) {
            return null;
        }

        $config = [
            "portalId" => $portalId,
            "formId"   => $formId,
            "target"   => self::TARGET_HTML_CONTAINER_ID,
        ];

        $region = $this->helper->getSellerNotifyFormRegion();
        if ($region) {
            $config["region"] = $region;
        }

        return $config;
    }
}

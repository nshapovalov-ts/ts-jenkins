<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Block;

use Magento\Framework\View\Element\Template;

/**
 * CmsListBlock class
 */
class CmsListBlock extends Template
{
    /** @var string */
    public const XML_PATH_DEBUG_MODE = 'retailplace_insider/insider_debug/insider_debug_enabled';
    public const XML_PATH_SKUS = 'retailplace_insider/insider_debug/skus';

    /**
     * Is insider debug mode enabled
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->_scopeConfig->isSetFlag(self::XML_PATH_DEBUG_MODE);
    }

    /**
     * Get product skus
     *
     * @return string|null
     */
    public function getSkus(): ?string
    {
        return (string) $this->_scopeConfig->getValue(self::XML_PATH_SKUS);
    }
}

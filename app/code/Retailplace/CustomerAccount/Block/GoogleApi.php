<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class GoogleApi extends Template
{
    /**
     * Get config value.
     *
     * @param string $path
     * @return string|null
     */
    public function getConfig($path)
    {
        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }
}

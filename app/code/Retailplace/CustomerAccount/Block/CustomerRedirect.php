<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block;

use Magento\Framework\View\Element\Template;

class CustomerRedirect extends Template
{
    /**
     * @return bool
     */
    public function needRedirectEdit(): bool
    {
        return ($this->getRequest()->getParam('redirect') == 'edit');
    }
}

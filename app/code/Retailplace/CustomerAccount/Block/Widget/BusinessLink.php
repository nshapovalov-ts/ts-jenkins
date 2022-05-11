<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

use Magento\Customer\Block\Widget\AbstractWidget;

class BusinessLink extends AbstractWidget
{
    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'business_link';

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        // default template location
        $this->setTemplate('Retailplace_CustomerAccount::widget/businesslink.phtml');
    }

}

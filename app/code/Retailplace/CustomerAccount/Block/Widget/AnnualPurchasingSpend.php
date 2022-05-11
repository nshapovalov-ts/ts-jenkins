<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

use Magento\Customer\Api\Data\CustomerInterface;

/**
 *
 * @method CustomerInterface getObject()
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class AnnualPurchasingSpend extends AbstractWidgetOption
{
    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'annual_purchasing_spend';

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setAttributeCode(self::ATTRIBUTE_CODE);
        // default template location
        $this->setTemplate('Retailplace_CustomerAccount::widget/preferences/base/selecttext.phtml');
    }
}

<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Widget for showing CategoriesBuy.
 *
 * @method CustomerInterface getObject()
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class CategoriesBuy extends AbstractWidgetOption
{
    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'categories_usually_buy';

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setAttributeCode(self::ATTRIBUTE_CODE);
        // default template location
        $this->setTemplate('Retailplace_CustomerAccount::widget/preferences/multiselectimg.phtml');
    }
}

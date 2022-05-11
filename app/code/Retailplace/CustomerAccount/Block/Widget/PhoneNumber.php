<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

use Magento\Customer\Block\Widget\AbstractWidget;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Widget for showing phone number.
 *
 * @method CustomerInterface getObject()
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class PhoneNumber extends AbstractWidget
{
    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'phone_number';

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        // default template location
        $this->setTemplate('Retailplace_CustomerAccount::widget/phonenumber.phtml');
    }

    /**
     * @return mixed|string
     */
    public function getPhoneNumberValue()
    {
        if ($phoneNumber = $this->getObject()->getCustomAttribute(self::ATTRIBUTE_CODE)) {
            return $phoneNumber->getValue();
        }
        return '';
    }
}

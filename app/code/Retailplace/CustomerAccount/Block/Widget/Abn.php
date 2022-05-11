<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

use Magento\Customer\Block\Widget\AbstractWidget;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Widget for showing Abn.
 *
 * @method CustomerInterface getObject()
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Abn extends AbstractWidget
{
    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'abn';

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        // default template location
        $this->setTemplate('Retailplace_CustomerAccount::widget/abn.phtml');
    }

    /**
     * @return mixed|string
     */
    public function getAbnValue()
    {
        if ($phoneNumber = $this->getObject()->getCustomAttribute(self::ATTRIBUTE_CODE)) {
            return $phoneNumber->getValue();
        }
        return '';
    }
}

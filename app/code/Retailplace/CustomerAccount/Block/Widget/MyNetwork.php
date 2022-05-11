<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Widget for showing my network.
 *
 * @method CustomerInterface getObject()
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class MyNetwork extends AbstractWidgetOption
{
    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'my_network';

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setAttributeCode(self::ATTRIBUTE_CODE);
        // default template location
        $this->setTemplate('Retailplace_CustomerAccount::widget/preferences/selectimg.phtml');
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        $attribute = $this->getAttribute();
        $customerValues = $this->getCustomerValues();
        $options = $attribute->getOptions();
        $data = [];
        foreach ($options as $option) {
            if (empty($option->getValue())) {
                continue;
            }
            $selected = false;
            if (is_array($customerValues) && in_array($option->getValue(), $customerValues)) {
                $selected = true;
            }

            $data[$option->getValue()] = [
                'label' => $option->getLabel(),
                'value' => $option->getValue(),
                'selected' => $selected,
                'icon' => $this->getImage('Retailplace_CustomerAccount::images/mynetwork/' . $option->getValue() . '.png')
            ];
        }

        return $data;
    }

    /**
     * @param $fileId
     * @return string
     */
    private function getImage($fileId)
    {
        return $this->getViewFileUrl($fileId);
    }
}

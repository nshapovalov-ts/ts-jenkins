<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

class Industry extends AbstractWidgetOption
{
    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'industry';

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setAttributeCode(self::ATTRIBUTE_CODE);
        // default template location
        $this->setTemplate('Retailplace_CustomerAccount::widget/preferences/industry.phtml');
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
                'icon' => $this->getImage('Retailplace_CustomerAccount::images/industry/' . $option->getValue() . '.png')
            ];
        }

        //Force to move other to final
        //@TODO need to find a proper solution
        $other = $data['other'];
        unset($data['other']);
        array_push($data, $other);

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

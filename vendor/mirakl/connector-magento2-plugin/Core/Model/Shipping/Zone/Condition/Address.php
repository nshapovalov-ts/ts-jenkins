<?php
namespace Mirakl\Core\Model\Shipping\Zone\Condition;

class Address extends \Magento\SalesRule\Model\Rule\Condition\Address
{
    /**
     * {@inheritdoc}
     */
    public function loadAttributeOptions()
    {
        return $this->setAttributeOption(static::getAttributes());
    }

    /**
     * @return  array
     */
    public static function getAttributes()
    {
        return [
            'postcode'   => __('Shipping Postcode'),
            'region'     => __('Shipping Region'),
            'region_id'  => __('Shipping State/Province'),
            'country_id' => __('Shipping Country'),
            'city'       => __('Shipping City'),
            'street'     => __('Shipping Street'),
        ];
    }
}

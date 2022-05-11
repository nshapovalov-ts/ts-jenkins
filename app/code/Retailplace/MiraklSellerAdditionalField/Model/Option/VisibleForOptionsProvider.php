<?php 
namespace Retailplace\MiraklSellerAdditionalField\Model\Option;

class VisibleForOptionsProvider implements \Magento\Framework\Option\ArrayInterface
{
   /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array("value" => "<value>", "label"=> "<label>"), ...)
     */
    public function toOptionArray()
    {
       
        return [
            ['value' => 1, 'label' => __('Retailer - for retailing purposes')],
            ['value' => 2, 'label' => __('Non retailer - for retailing purposes')],
            ['value' => 3, 'label' => __('For Business Use')],
            ['value' => 4, 'label' => __('For Corporate Gifting ')],
        ];
    
    }
}
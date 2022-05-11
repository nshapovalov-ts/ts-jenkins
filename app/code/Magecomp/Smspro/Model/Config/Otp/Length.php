<?php
namespace Magecomp\Smspro\Model\Config\Otp;

class Length implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        $options = [];
        for($len = 1; $len <=10; $len++)
        {
            $options[] = ['value' => $len,'label' => $len];
        }
        return $options;
    }
}
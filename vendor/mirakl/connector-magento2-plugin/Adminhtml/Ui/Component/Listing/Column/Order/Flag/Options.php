<?php
namespace Mirakl\Adminhtml\Ui\Component\Listing\Column\Order\Flag;

use Magento\Framework\Data\OptionSourceInterface;

class Options implements OptionSourceInterface
{
    /**
     * @return  array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'magento', 'label' => __('Operator')],
            ['value' => 'marketplace', 'label' => __('Marketplace')],
            ['value' => 'mixed', 'label' => __('Mixed')],
        ];
    }
}
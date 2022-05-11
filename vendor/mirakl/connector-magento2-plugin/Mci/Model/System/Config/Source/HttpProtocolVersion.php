<?php
namespace Mirakl\Mci\Model\System\Config\Source;

class HttpProtocolVersion implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => '1.0',
                'value' => '1.0',
            ],
            [
                'label' => '1.1',
                'value' => '1.1',
            ],
        ];
    }

    /**
     * @return  array
     */
    public function toArray()
    {
        return ['1.0' => '1.0', '1.1' => '1.1'];
    }
}

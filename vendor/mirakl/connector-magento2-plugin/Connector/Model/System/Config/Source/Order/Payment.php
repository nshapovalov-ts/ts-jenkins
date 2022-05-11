<?php
namespace Mirakl\Connector\Model\System\Config\Source\Order;

use Magento\Framework\Option\ArrayInterface;
use Mirakl\Api\Helper\Payment as OrderPayment;

class Payment implements ArrayInterface
{
    /**
     * Retrieves order payment workflow types
     *
     * @return  array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => OrderPayment::PAY_ON_ACCEPTANCE,
                'label' => OrderPayment::PAY_ON_ACCEPTANCE
            ],
            [
                'value' => OrderPayment::PAY_ON_DELIVERY,
                'label' => OrderPayment::PAY_ON_DELIVERY
            ],
        ];

        return $options;
    }
}
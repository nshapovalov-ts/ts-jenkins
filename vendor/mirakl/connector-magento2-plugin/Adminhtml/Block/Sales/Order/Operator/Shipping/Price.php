<?php
namespace Mirakl\Adminhtml\Block\Sales\Order\Operator\Shipping;

use Magento\Sales\Model\Order;

/**
 * @method Order getOrder()
 * @method $this setOrder(Order $order)
 */
class Price extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'Mirakl_Adminhtml::order/operator/shipping/price.phtml';
}
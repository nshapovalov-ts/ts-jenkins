<?php

namespace Magecomp\Smspro\Plugin;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\ObjectManagerInterface;

class PluginBtnOrderView
{
    protected $object_manager;
    protected $_backendUrl;

    public function __construct(
        ObjectManagerInterface $om,
        UrlInterface $backendUrl
    ) {
        $this->object_manager = $om;
        $this->_backendUrl = $backendUrl;
    }

    public function beforeSetLayout( \Magento\Sales\Block\Adminhtml\Order\View $subject )
    {
        $sendOrderSms = $this->_backendUrl->getUrl('magecompsms/send/order/order_id/'.$subject->getOrderId() );
        $subject->addButton(
            'sendordersms',
            [
                'label' => __('ReSend SMS'),
                'onclick' => "setLocation('" . $sendOrderSms . "')",
                'class' => 'ship primary'
            ]
        );

        return null;
    }

}
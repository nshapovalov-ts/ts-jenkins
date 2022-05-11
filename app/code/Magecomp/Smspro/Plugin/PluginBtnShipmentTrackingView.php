<?php

namespace Magecomp\Smspro\Plugin;


use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\ObjectManagerInterface;

class PluginBtnShipmentTrackingView
{
    protected $object_manager;
    protected $_backendUrl;

    public function __construct(
        ObjectManagerInterface $om,
        UrlInterface $backendUrl
    )
    {
        $this->object_manager = $om;
        $this->_backendUrl = $backendUrl;
    }


    public function beforeSetLayout( \Magento\Shipping\Block\Adminhtml\View $subject )
    {

        $sendOrderSms = $this->_backendUrl->getUrl('magecompsms/send/shipmenttrack/shipment_id/'.$subject->getShipment()->getId() );
        $subject->addButton(
            'sentracksms',
            [
                'label' => __('Send Tracking SMS'),
                'onclick' => "setLocation('" . $sendOrderSms . "')",
                'class' => 'ship primary'
            ]
        );

        return null;
    }

}
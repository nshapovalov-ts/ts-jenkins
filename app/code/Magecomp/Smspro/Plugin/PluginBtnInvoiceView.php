<?php

namespace Magecomp\Smspro\Plugin;


use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\ObjectManagerInterface;

class PluginBtnInvoiceView
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


    public function beforeSetLayout( \Magento\Sales\Block\Adminhtml\Order\Invoice\View $subject )
    {

        $sendOrderSms = $this->_backendUrl->getUrl('magecompsms/send/invoice/invoice_id/'.$subject->getInvoice()->getId() );
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
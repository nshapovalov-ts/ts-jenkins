<?php
namespace Mirakl\Adminhtml\Block\Sales\Order;

class View extends \Magento\Sales\Block\Adminhtml\Order\View
{
    /**
     * Add some buttons for Mirakl
     */
    protected function _construct()
    {
        parent::_construct();

        if ($this->isMiraklOrder()) {
            $orderEditDialog = json_encode([
                'message' => __('Are you sure? This order will be sent to Mirakl platform.'),
                'url' => $this->getSendMiraklUrl(),
            ]);
            $onclickJs = "jQuery('#mirakl_send_order').orderEditDialog($orderEditDialog).orderEditDialog('showDialog');";

            $this->addButton('mirakl_send_order', [
                'label' => __('Send to Mirakl'),
                'class' => 'mirakl',
                'onclick' => $onclickJs,
                'data_attribute' => [
                    'mage-init' => '{"orderEditDialog":{}}',
                ]
            ]);
        }
    }

    /**
     * Add a message if order has already been sent to Mirakl
     *
     * @return  $this
     */
    protected function _prepareLayout()
    {
        if ($this->isMiraklOrder() && $this->getOrder()->getMiraklSent()) {
            $this->getLayout()
                ->getMessagesBlock()
                ->addNotice(__('This order has been sent to Mirakl with commercial id: %1.',
                    $this->getOrder()->getIncrementId()));
        }

        parent::_prepareLayout();

        return $this;
    }

    /**
     * @return  string
     */
    public function getSendMiraklUrl()
    {
        return $this->getUrl('mirakl/order/send', ['order_id' => $this->getOrderId()]);
    }

    /**
     * @return  bool
     */
    protected function isMiraklOrder()
    {
        return $this->getOrder()->getMiraklShippingZone();
    }
}
<?php

namespace Magecomp\Smspro\Block\Adminhtml\Order\View\Tab;


class Custom extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_template = 'order/view/tab/custom.phtml';

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        array $data = []
    )
    {

        $this->_coreRegistry = $registry;
        $this->customerFactory = $customerFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Retrieve order increment id
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Send Custom SMS');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Send Custom SMS');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    public function getMobileNumber()
    {
        $customer = $this->customerFactory->create()->load($this->getOrder()->getCustomerId());
        $mobilenumber = $this->getOrder()->getBillingAddress()->getTelephone();
        $mobile = $customer->getMobilenumber();

        if ($mobile != '' && $mobile != null) {
            $mobilenumber = $mobile;
        }
        return $mobilenumber;
    }
}
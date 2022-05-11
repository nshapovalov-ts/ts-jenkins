<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Observer;

use Amasty\CustomerAttributes\Model\Attribute;
use Magento\Framework\Event\ObserverInterface;

class OrderSender implements ObserverInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;

    /**
     * @var \Amasty\CustomerAttributes\Model\Customer\GuestAttributesFactory
     */
    private $guestAttributesFactory;

    /**
     * @var \Magento\Customer\Model\AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * @var \Amasty\CustomerAttributes\Helper\Session
     */
    protected $sessionHelper;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AttributeMetadataDataProvider $attributeMetadataDataProvider,
        \Amasty\CustomerAttributes\Model\Customer\GuestAttributesFactory $guestAttributesFactory,
        \Amasty\CustomerAttributes\Helper\Session $sessionHelper
    ) {
        $this->objectManager = $objectManager;
        $this->customerFactory = $customerFactory;
        $this->guestAttributesFactory = $guestAttributesFactory;
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->sessionHelper = $sessionHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport = $observer->getTransport();
        /** @var \Magento\Sales\Model\Order|null $order */
        $order = null;
        if (is_object($transport)) {
            $order = $transport->getOrder();
        } elseif (is_array($transport) && array_key_exists('order', $transport)) {
            $order = $transport['order'];
        }

        if ($order) {
            $order->setCustomer($this->getCustomer($order));
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Customer\Model\Customer
     */
    protected function getCustomer($order)
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customerFactory->create();
        $customer->setStore($order->getStore());
        $customer->setWebsiteId($order->getStore()->getWebsite()->getId());
        if ($order->getCustomerId()) {
            $customer->loadByEmail($order->getCustomerEmail());
        } else {
            /** @var \Amasty\CustomerAttributes\Model\Customer\GuestAttributes $model */
            $model = $this->guestAttributesFactory->create()->loadByOrderId($order->getId());
            if ($model && $model->getId()) {
                foreach ($model->getData() as $key => $value) {
                    if ($key == 'id') {
                        continue;
                    }
                    if ($value) {
                        $customer->setData($key, $value);
                    }
                }
            } else {
                $customAttributes = $this->sessionHelper->getCustomerAttributesFromSession();

                if ($customAttributes) {
                    foreach ($customAttributes as $key => $value) {
                        if ($value) {
                            $customer->setData($key, $value);
                        }
                    }
                }
            }
        }

        /*convert option value to option text*/
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer',
            Attribute::AMASTY_ATTRIBUTE_CODE
        );
        foreach ($attributes as $attribute) {
            if ($attribute->usesSource()) {
                $code = $attribute->getAttributeCode();
                $value = $customer->getData($code);
                $value = is_array($value) ? implode(',', $value) : $value;
                $value = $attribute->getSource()->getOptionText($value);
                $value = is_array($value) ? implode(',', $value) : $value;
                $customer->setData($code, $value);
            }
        }

        return $customer;
    }
}

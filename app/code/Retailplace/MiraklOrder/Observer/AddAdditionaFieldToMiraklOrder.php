<?php

namespace Retailplace\MiraklOrder\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;

class AddAdditionaFieldToMiraklOrder implements \Magento\Framework\Event\ObserverInterface
{
    protected $customerRepository;

    public function __construct(
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerRepository = $customerRepository;
    }

    public function getAttributeValue($customerId, $customerAttributeCode)
    {
        $customer = $this->customerRepository->getById($customerId);
        return $customer->getCustomAttribute($customerAttributeCode) ? $customer->getCustomAttribute($customerAttributeCode)->getValue() : "";
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getData('order');
        $createOrder = $observer->getData('create_order');
        $customerId = $order->getCustomerId();
        $abn = $this->getAttributeValue($customerId, 'abn');
        $businessName = $this->getAttributeValue($customerId, 'business_name');
        $orderAddtionalFields = [];
        $orderAddtionalFields[] = [
            'type' => 'STRING',
            'code' => 'abn',
            'value' => $abn
        ];
        $orderAddtionalFields[] = [
            'type' => 'STRING',
            'code' => 'businessname',
            'value' => $businessName
        ];

        $createOrder->setData('order_additional_fields', $orderAddtionalFields);
        return $this;
    }
}

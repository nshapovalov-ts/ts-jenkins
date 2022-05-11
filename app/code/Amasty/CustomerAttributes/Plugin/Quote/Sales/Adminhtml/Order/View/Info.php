<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */
namespace Amasty\CustomerAttributes\Plugin\Quote\Sales\Adminhtml\Order\View;

use Magento\Customer\Api\CustomerRepositoryInterface;

class Info
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    private $attribute;

    /**
     * @var \Magento\Eav\Model\Entity
     */
    private $eavEntity;

    /**
     * @var \Amasty\CustomerAttributes\Model\Customer\GuestAttributesFactory
     */
    private $guestAttributesFactory;

    /**
     * Info constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Amasty\CustomerAttributes\Model\Customer\GuestAttributesFactory $guestAttributesFactory
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param \Magento\Eav\Model\Entity $eavEntity
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        \Amasty\CustomerAttributes\Model\Customer\GuestAttributesFactory $guestAttributesFactory,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute,
        \Magento\Eav\Model\Entity $eavEntity
    ) {
        $this->customerRepository = $customerRepository;
        $this->attribute = $attribute;
        $this->eavEntity = $eavEntity;
        $this->guestAttributesFactory = $guestAttributesFactory;
    }

    public function beforeToHtml(
        $subject
    ) {
        $order = $this->_getOrder($subject);
        if (!$order) {
            return;
        }

        $customerId = $order->getCustomerId();
        if ($customerId > 0) {
            $customer           = $this->customerRepository->getById($customerId);
            $customerAttributes = $customer->getCustomAttributes();
            if ($customerAttributes) {
                foreach ($customerAttributes as $customerAttribute) {
                    $this->addAttributeToOrder(
                        $customerAttribute->getAttributeCode(),
                        $customerAttribute->getValue(),
                        $order
                    );
                }
            }
        } else {
            $model = $this->guestAttributesFactory->create()
                ->loadByOrderId($order->getId());

            if ($model && $model->getId()) {
                foreach ($model->getData() as $key => $value) {
                    if ($key == 'id') {
                        continue;
                    }
                    $this->addAttributeToOrder($key, $value, $order);
                }
            }
        }

        $subject->setOrder($order);
    }

    /**
     * @param string $code
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     */
    protected function getByCode($code)
    {
        $entityTypeId = $this->eavEntity->setType(\Magento\Customer\Model\Customer::ENTITY)->getTypeId();

        $attribute = $this->attribute->loadByCode(
            $entityTypeId,
            $code
        );

        return $attribute;
    }

    /**
     * @param string $code
     * @param string $value
     * @param \Magento\Sales\Model\Order $order
     */
    protected function addAttributeToOrder($code, $value, $order)
    {
        if ($value) {
            $attribute = $this->getByCode($code);
            if ($attribute->getOnOrderView()) {
                $name = 'customer_' . $code;
                $order->setData($name, $value);
            }
        }
    }

    protected function _getOrder($subject)
    {
        try {
            $order = $subject->getOrder();
        } catch (\Magento\Framework\Exception\LocalizedException $ex) {
            return false;
        }

        return $order;
    }
}

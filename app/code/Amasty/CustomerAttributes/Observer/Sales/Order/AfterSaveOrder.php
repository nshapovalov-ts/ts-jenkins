<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Observer\Sales\Order;

use Amasty\CustomerAttributes\Helper\Session;
use Amasty\CustomerAttributes\Model\Attribute;
use Amasty\CustomerAttributes\Model\CustomerAttributesProcessor;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

/**
 * Class AfterSaveOrder for 'sales_order_save_after' event
 */
class AfterSaveOrder implements ObserverInterface
{
    /**
     * @var Session
     */
    private $sessionHelper;

    /**
     * @var CustomerAttributesProcessor
     */
    private $customerAttributesProcessor;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    public function __construct(
        Session $sessionHelper,
        CustomerAttributesProcessor $customerAttributesProcessor,
        RequestInterface $request,
        AttributeMetadataDataProvider $attributeMetadataDataProvider
    ) {
        $this->sessionHelper = $sessionHelper;
        $this->customerAttributesProcessor = $customerAttributesProcessor;
        $this->request = $request;
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order || !$order->getCustomerIsGuest()) {
            return;
        }

        if (empty($order->getRemoteIp()) && empty($order->getRelationChildId())) {
            $customAttributes = $this->getCustomerAttributesFromRequest();
        } else {
            $customAttributes = $this->sessionHelper->getCustomerAttributesFromSession();
        }

        if ($customAttributes) {
            $this->customerAttributesProcessor->saveCustomerAttributesGuest($order->getId(), $customAttributes);
        }
    }

    /**
     * @return array
     */
    private function getCustomerAttributesFromRequest()
    {
        $customAttributes = [];
        $orderParams = $this->request->getParam('order');
        $accountAttributes = $orderParams['account'] ?? [];

        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer',
            Attribute::AMASTY_ATTRIBUTE_CODE
        );

        /** @var \Magento\Customer\Model\Attribute $attribute */
        foreach ($attributes->getItems() as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            if (isset($accountAttributes[$attributeCode])) {
                $customAttributes[$attributeCode] = $accountAttributes[$attributeCode];
            }
        }

        return $customAttributes;
    }
}

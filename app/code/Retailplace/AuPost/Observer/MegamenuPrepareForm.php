<?php

/**
 * Retailplace_AuPost
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AuPost\Observer;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class MegamenuPrepareForm
 */
class MegamenuPrepareForm implements ObserverInterface
{
    /** @var string */
    public const MEGAMENU_CUSTOMER_GROUP_ID = 'customer_group_id';
    public const MEGAMENU_CUSTOMER_GROUP_ID_TITLE = 'Customer Group Id';

    /** @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory */
    private $customerGroupCollectionFactory;

    /**
     * MegamenuPrepareForm constructor.
     *
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupCollectionFactory
     */
    public function __construct(CustomerGroupCollectionFactory $customerGroupCollectionFactory)
    {
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
    }

    /**
     * Add new field to the Megamenu Item Edit form in Adminhtml
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $observer->getData('form');
        $fieldset = $form->getElement('base_fieldset');
        $fieldset->addField(
            self::MEGAMENU_CUSTOMER_GROUP_ID,
            'select',
            [
                'label' => __(self::MEGAMENU_CUSTOMER_GROUP_ID_TITLE),
                'title' => __(self::MEGAMENU_CUSTOMER_GROUP_ID_TITLE),
                'name' => self::MEGAMENU_CUSTOMER_GROUP_ID,
                'values' => $this->getCustomerGroupList(),
                'disabled' => false
            ]
        );
    }

    /**
     * Get Customer Groups list
     *
     * @return array
     */
    private function getCustomerGroupList(): array
    {
        $customerGroupCollection = $this->customerGroupCollectionFactory->create();

        return $customerGroupCollection->toOptionArray();
    }
}

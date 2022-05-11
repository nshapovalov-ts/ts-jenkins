<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */
namespace Amasty\CustomerAttributes\Observer;

use Magento\Framework\Event\ObserverInterface;

class ChangeAttribute implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory
     */
    private $_collectionFactory;

    /**
     * @var \Amasty\CustomerAttributes\Helper\Collection
     */
    private $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Amasty\CustomerAttributes\Model\Customer\GuestAttributesFactory
     */
    private $guestAttributesFactory;

    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $collectionFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Amasty\CustomerAttributes\Helper\Collection $helper,
        \Psr\Log\LoggerInterface $logger,
        \Amasty\CustomerAttributes\Model\Customer\GuestAttributesFactory $guestAttributesFactory
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->helper = $helper;
        $this->_objectManager = $objectManager;
        $this->_collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->guestAttributesFactory = $guestAttributesFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * reindex structure customer grid
         */
        $this->_updateAttributeTable();
        $indexer = $this->indexerRegistry->get(\Magento\Customer\Model\Customer::CUSTOMER_GRID_INDEXER_ID);
        try {
            $indexer->reindexAll();
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
        }
    }

    protected function _updateAttributeTable()
    {
        $collection = $this->_collectionFactory->create()
            ->addVisibleFilter();
        $collection = $this->helper->addFilters(
            $collection,
            'eav_attribute',
            [
                "is_user_defined = 1",
                "attribute_code != 'customer_activated' "
            ]
        );

        $attributeName = [];
        $attributeType = [];
        foreach ($collection as $attribute) {
            $attributeName[] = $attribute['attribute_code'];
            $attributeType[$attribute['attribute_code']]
                = $attribute['backend_type'];
        }

        $currentFields = $this->_getFields();

        $namesAdd = array_diff($attributeName, $currentFields);

        $namesDel = array_diff($currentFields, $attributeName);

        $model = $this->guestAttributesFactory->create();
        /** @var \Amasty\CustomerAttributes\Model\Customer\GuestAttributes $model */

        $model->deleteFields($namesDel);
        $model->addFields($namesAdd, $attributeType);
    }

    /**
     * get list of fields for amcustomerattr/guest
     */
    protected function _getFields()
    {
        $model = $this->guestAttributesFactory->create();
        /** @var \Amasty\CustomerAttributes\Model\Customer\GuestAttributes $model */
        $columns = $model->getFields();

        return $columns;
    }
}

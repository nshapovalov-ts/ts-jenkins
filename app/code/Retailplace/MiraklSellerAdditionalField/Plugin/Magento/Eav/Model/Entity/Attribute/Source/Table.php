<?php
/**
 * A Magento 2 module named Retailplace/MiraklSellerAdditionalField
 * Copyright (C) 2019
 *
 * This file included in Retailplace/MiraklSellerAdditionalField is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Retailplace\MiraklSellerAdditionalField\Plugin\Magento\Eav\Model\Entity\Attribute\Source;

use Magento\Customer\Controller\RegistryConstants;

class Table
{
    protected $industryExclusionsFactory;
    protected $customerSession;

    private $_coreRegistry;
    private $_options;
    private $customerResource;

    private $customerFactory;

    public function __construct(
        \Magento\Customer\Model\SessionFactory $customerSession,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Retailplace\MiraklSellerAdditionalField\Model\IndustryExclusionsFactory $industryExclusionsFactory
    ) {
        $this->customerResource = $customerResource;
        $this->customerFactory = $customerFactory;
        $this->_coreRegistry = $registry;
        $this->customerSession = $customerSession->create();
        $this->_industryExclusionsFactory = $industryExclusionsFactory;
    }
    public function aroundGetAllOptions(
        \Magento\Eav\Model\Entity\Attribute\Source\Table $subject,
        \Closure $proceed,
        $withEmpty = true,
        $defaultValues = false
    ) {
        if ($subject->getAttribute() && 'industry' == $subject->getAttribute()->getAttributeCode()) {
            $this->_options = [];
            $industryExclusionsCollection = $this->_industryExclusionsFactory->create()->getCollection();
            $industryExclusionsCollection->addFieldToSelect('code');
            $industryExclusionsCollection->addFieldToSelect('label');
            $select = $industryExclusionsCollection->getSelect();
            $connection = $industryExclusionsCollection->getConnection();

            $industryOptions = $connection->fetchAssoc((clone $select)->where("`status` IS NULL OR `status` != '2'")->order('label ASC'));
            foreach ($industryOptions as $industryOption) {
                $this->_options[] = ['value' => $industryOption['code'], 'label' => __($industryOption['label'])];
            }

            return $this->_options;

            /*$tradeSqaureOption = 1;
            $adminCustomerId = $this->getCustomerId();
            if ($customerId = $this->customerSession->getCustomer()->getId() || $adminCustomerId) {
                if ($adminCustomerId) {
                    $customerData = $this->getCustomer($this->getCustomerId());
                } else {
                    $customerData = $this->customerSession->getCustomer();
                }

                $tradesquare = $customerData->getData('tradesquare');
                $tradesquareAttribute = $customerData->getResource()->getAttribute('tradesquare');
                $tradesquareOptionText = "";
                if ($tradesquareAttribute->usesSource() && $tradesquare) {
                    $tradesquareOptionText = trim($tradesquareAttribute->getSource()->getOptionText($tradesquare));
                }
                $tradeSqaureOptions['Retailer - for retailing purposes'] = 1;
                $tradeSqaureOptions['Non retailer - for retailing purposes'] = 2;
                $tradeSqaureOptions['For Business Use'] = 3;
                $tradeSqaureOptions['For Corporate Gifting'] = 4;
                $tradeSqaureOption = $tradeSqaureOptions[$tradesquareOptionText] ?? "";
            }
            if (!$tradeSqaureOption) {
                $tradeSqaureOption = 1;
            }
            $industryExclusionsCollection = $this->_industryExclusionsFactory->create()->getCollection();
            $industryExclusionsCollection->addFieldToSelect('code');
            $industryExclusionsCollection->addFieldToSelect('label');
            $select = $industryExclusionsCollection->getSelect();
            $connection = $industryExclusionsCollection->getConnection();

            $industryOptions = $connection->fetchAssoc((clone $select)->where("FIND_IN_SET($tradeSqaureOption, `visible_for`)"));
            foreach ($industryOptions as $industryOption) {
                $this->_options[] = ['value' => $industryOption['code'], 'label' => __($industryOption['label'])];
            }
            return $this->_options;*/
        }

        $result = $proceed($withEmpty, $defaultValues);
        return $result;
    }
    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }
    public function getCustomer($id)
    {
        $customerModel = $this->customerFactory->create();
        $this->customerResource->load($customerModel, $id);
        return $customerModel;
    }
}

<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Block\Checkout;

use Magento\Framework\View\Element\Template;

class Attributes extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory
     */
    private $customerAttributeCollectionFactory;

    /**
     * @var \Amasty\CustomerAttributes\Helper\Collection
     */
    private $helper;

    /**
     * Attributes constructor.
     * @param Template\Context $context
     * @param \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $customerAttributeCollectionFactory
     * @param \Amasty\CustomerAttributes\Helper\Collection $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $customerAttributeCollectionFactory,
        \Amasty\CustomerAttributes\Helper\Collection $helper,
        array $data = []
    ) {
        $this->customerAttributeCollectionFactory = $customerAttributeCollectionFactory;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getAttributeCodes()
    {
        $collection = $this->customerAttributeCollectionFactory->create()
            ->addVisibleFilter();
        $collection = $this->helper->addFilters(
            $collection,
            'eav_attribute',
            [
                "is_user_defined = 1",
                "attribute_code != 'customer_activated' "
            ]
        );

        $codesArray = \Zend_Json::encode($collection->getColumnValues('attribute_code'));

        return $codesArray;
    }
}

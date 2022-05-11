<?php

namespace Magecomp\Smspro\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;

class CustomerGroups implements ArrayInterface
{
    protected $options;
    protected $customerGroupCollection;

    public function __construct(CollectionFactory $customerGroupCollection)
    {
        $this->customerGroupCollection = $customerGroupCollection;
    }

    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->customerGroupCollection->create()->setRealGroupsFilter()->loadData()->toOptionArray();
            array_unshift($this->options, ['value' => '0', 'label' => __('NOT LOGGED IN')]);
        }
        return $this->options;
    }
}

<?php
namespace Mirakl\Adminhtml\Ui\Component\Filters\Type\Order;

class Flag extends \Magento\Ui\Component\Filters\Type\Select
{
    /**
     * {@inheritdoc}
     */
    protected function applyFilter()
    {
        if (isset($this->filterData[$this->getName()])) {
            $value = $this->filterData[$this->getName()];
            if (!empty($value) || is_numeric($value)) {
                $filter = $this->filterBuilder->setConditionType('order_flag')
                    ->setField($this->getName())
                    ->setValue($value)
                    ->create();

                $this->getContext()->getDataProvider()->addFilter($filter);
            }
        }
    }
}
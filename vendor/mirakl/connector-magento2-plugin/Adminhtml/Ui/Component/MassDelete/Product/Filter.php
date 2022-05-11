<?php
namespace Mirakl\Adminhtml\Ui\Component\MassDelete\Product;

use Magento\Framework\Data\Collection\AbstractDb;

class Filter extends \Magento\Ui\Component\MassAction\Filter
{
    /**
     * {@inheritdoc}
     */
    protected function applySelection(AbstractDb $collection)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection */
        $collection = parent::applySelection($collection);
        $collection->addAttributeToSelect('mirakl_sync');

        return $collection;
    }
}
<?php

namespace Retailplace\MiraklMci\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AttributePrepare implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var DataObject $attribute */
        $attribute = $observer->getData('attribute');
        if ($attribute->getCode() == 'item_variant') {
            $attribute->addData([
                'type'           => 'TEXT',
                'type-parameter' => null
            ]);
        }
    }
}

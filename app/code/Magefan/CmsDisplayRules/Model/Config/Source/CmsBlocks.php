<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Model\Config\Source;

use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;

/**
 * Class OptionBlockCms add select with cms blocks
 */
class CmsBlocks extends CmsPages
{
    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Option constructor.
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array|void
     */
    public function toOptionArray()
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()->order('identifier');
        $items = [[
            'label' => __('Display nothing instead'),
            'value' => 0
        ]];
        foreach ($collection as $item) {
            $items[] = [
                'label' => $item->getTitle() . ' (#' . $item->getId() . ' ' . $item->getIdentifier() . ')' ,
                'value' => $item->getId()
            ];
        }
        return $items;
    }
}

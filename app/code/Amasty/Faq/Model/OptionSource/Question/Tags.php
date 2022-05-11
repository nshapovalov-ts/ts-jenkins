<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Model\OptionSource\Question;

use Amasty\Faq\Api\Data\TagInterface;
use Amasty\Faq\Model\ResourceModel\Tag\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;

class Tags implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->collectionFactory->create();
        $tags = $collection->getData();
        $result = [];
        foreach ($tags as $tag) {
            $result[] = ['value' => $tag[TagInterface::TAG_ID], 'label' => $tag[TagInterface::TITLE]];
        }

        return $result;
    }
}

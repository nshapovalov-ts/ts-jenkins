<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Model\ResourceModel\Block;

/**
 * Class Collection of Blocks
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    public function _construct()
    {
        $this->_init(
            \Magefan\CmsDisplayRules\Model\Block::class,
            \Magefan\CmsDisplayRules\Model\ResourceModel\Block::class
        );
    }
}

<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Model;

/**
 * Class Page model
 */
class Page extends \Magento\Catalog\Model\AbstractModel
{

    public function _construct()
    {
        $this->_init(\Magefan\CmsDisplayRules\Model\ResourceModel\Page::class);
    }
}

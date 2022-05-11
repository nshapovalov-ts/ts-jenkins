<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Controller\Adminhtml\Category;

class NewAction extends \Amasty\Faq\Controller\Adminhtml\Category
{
    public function execute()
    {
        $this->_forward('edit');
    }
}

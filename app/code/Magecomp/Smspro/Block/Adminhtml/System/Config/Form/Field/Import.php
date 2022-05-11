<?php

namespace Magecomp\Smspro\Block\Adminhtml\System\Config\Form\Field;

class Import extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    public function getElementHtml()
    {
        $html = '';
        $html .= parent::getElementHtml();
        return $html;
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setType('file');
    }
}

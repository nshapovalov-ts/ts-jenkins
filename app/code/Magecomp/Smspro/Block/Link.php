<?php

namespace Magecomp\Smspro\Block;  

class Link extends \Magento\Framework\View\Element\Html\Link
{
    protected $_template = 'Magecomp_Smspro::link.phtml'; 
    public function getHref()  
   {  
     return $this->getUrl('smspro/customer/update');  
   }  
   public function getLabel()  
   {  
     return __('Verify Mobile Number');  
   }  
    public function _toHtml() 
    {
        if (!$this->_scopeConfig->isSetFlag('usertemplate/usermobileconfirm/enable')) 
        {
            return '';
        }
        return parent::_toHtml();
    }
}
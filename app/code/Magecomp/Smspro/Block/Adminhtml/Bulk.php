<?php

namespace Magecomp\Smspro\Block\Adminhtml;
class Bulk extends \Magento\Framework\View\Element\Template
{
    public function __construct( \Magento\Framework\View\Element\Template\Context $context )
    {
        parent::__construct($context);
    }

    public function sayHello()
    {
        return __('Bulk SMS :)');
    }
}
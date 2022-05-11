<?php
namespace Retailplace\Whitelistemaildomain\Block\Adminhtml\Whitelistemaildomain\Edit;

/**
 * Admin page left menu
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('whitelistemaildomain_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Domain Information'));
    }
}
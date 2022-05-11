<?php
namespace Mirakl\Process\Block\Adminhtml\Process;

use Magento\Backend\Block\Widget\Grid\Container;

class Grid extends Container
{
    /**
     * @return  void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->removeButton('add');

        // Add a Clear button that will delete all processes
        $confirm = $this->escapeJsQuote(__('Are you sure? This will delete all existing processes.'));
        $url = $this->getUrl('*/*/clear', ['_current' => true]);
        $this->addButton('clear', [
            'label'     => __('Clear All'),
            'onclick'   => "confirmSetLocation('$confirm', '$url')",
            'class'     => 'primary',
        ]);
    }
}

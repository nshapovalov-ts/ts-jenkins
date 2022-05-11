<?php
namespace Mirakl\Event\Block\Adminhtml\Event;

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
        $confirm = $this->escapeJsQuote(__('Are you sure? This will delete all existing events.'));
        $url = $this->getUrl('*/*/clear', ['_current' => true]);
        $this->addButton('clear', [
            'label'     => __('Clear All'),
            'onclick'   => "confirmSetLocation('$confirm', '$url')",
            'class'     => 'primary',
        ]);

        // Add a Run button that will execute events workflow
        $confirm = $this->escapeJsQuote(__('Are you sure? This will execute events workflow.'));
        $url = $this->getUrl('*/*/run', ['_current' => true]);
        $this->addButton('run', [
            'label'     => __('Run'),
            'onclick'   => "confirmSetLocation('$confirm', '$url')",
        ]);
    }
}

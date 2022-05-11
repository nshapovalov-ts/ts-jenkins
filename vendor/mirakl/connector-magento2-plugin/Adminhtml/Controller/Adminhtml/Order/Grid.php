<?php
namespace Mirakl\Adminhtml\Controller\Adminhtml\Order;

class Grid extends \Magento\Sales\Controller\Adminhtml\Order
{
    /**
     * Mirakl orders grid
     *
     * @return  \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->_initOrder();
        $resultLayout = $this->resultLayoutFactory->create();

        return $resultLayout;
    }
}
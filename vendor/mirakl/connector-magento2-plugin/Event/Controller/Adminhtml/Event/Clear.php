<?php
namespace Mirakl\Event\Controller\Adminhtml\Event;

class Clear extends AbstractEventAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            $this->getEventResource()->truncate();
            $this->messageManager->addSuccessMessage(__('Events have been deleted successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while deleting all events: %1.', $e->getMessage())
            );
        }

        $this->_redirect('*/*/');
    }
}

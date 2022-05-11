<?php
namespace Mirakl\Event\Controller\Adminhtml\Event;

class MassDelete extends AbstractEventAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('event_ids');

        if (empty($ids)) {
            return $this->redirectError(__('Please select events to delete.'));
        }

        try {
            $this->getEventResource()->deleteIds($ids);
            $this->messageManager->addSuccessMessage(__('Events have been deleted successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while deleting events: %1.', $e->getMessage())
            );
        }

        $this->_redirect('*/*/');
    }
}

<?php
namespace Mirakl\Event\Controller\Adminhtml\Event;

class Delete extends AbstractEventAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $event = $this->getEvent();

        if (!$event->getId()) {
            return $this->redirectError(__('This event no longer exists.'));
        }

        try {
            $this->getEventResource()->delete($event);
            $this->messageManager->addSuccessMessage(__('Event has been deleted successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while deleting the event: %1.', $e->getMessage())
            );
        }

        $this->_redirect('*/*/');
    }
}

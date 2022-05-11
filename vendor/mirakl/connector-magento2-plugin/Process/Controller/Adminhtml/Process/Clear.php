<?php
namespace Mirakl\Process\Controller\Adminhtml\Process;

class Clear extends AbstractProcessAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            $this->getProcessResource()->truncate();
            $this->messageManager->addSuccessMessage(__('Processes have been deleted successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while deleting all processes: %1.', $e->getMessage())
            );
        }

        $this->_redirect('*/*/');
    }
}

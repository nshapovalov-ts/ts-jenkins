<?php
namespace Mirakl\Process\Controller\Adminhtml\Process;

class MassDelete extends AbstractProcessAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('process_ids');

        if (empty($ids)) {
            return $this->redirectError(__('Please select processes to delete.'));
        }

        try {
            $this->getProcessResource()->deleteIds($ids);
            $this->messageManager->addSuccessMessage(__('Processes have been deleted successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while deleting processes: %1.', $e->getMessage())
            );
        }

        $this->_redirect('*/*/');
    }
}

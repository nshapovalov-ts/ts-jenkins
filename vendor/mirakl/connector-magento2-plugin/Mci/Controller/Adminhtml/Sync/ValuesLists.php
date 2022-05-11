<?php
namespace Mirakl\Mci\Controller\Adminhtml\Sync;

use Mirakl\Connector\Controller\Adminhtml\AbstractSync;
use Mirakl\Process\Model\Process;

class ValuesLists extends AbstractSync
{
    /**
     * Export operator attribute value lists to Mirakl
     */
    public function execute()
    {
        try {
            if (!$this->checkConnectorEnabled()) {
                return $this->redirectReferer();
            }

            /** @var Process $process */
            $process = $this->processFactory->create()
                ->setType(Process::TYPE_ADMIN)
                ->setName('VL01 synchronization')
                ->setHelper(\Mirakl\Mci\Helper\ValueList::class)
                ->setMethod('exportAttributes');

            $this->processResourceFactory->create()->save($process);
            $this->connectorConfig->setSyncDate('values_lists');
            $this->messageManager->addSuccessMessage(__('Attribute value lists will be exported asynchronously.'));
            $this->addRawSuccessMessage(__('Click <a href="%1">here</a> to view process output.', $process->getUrl()));

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while exporting attribute value lists (%1).', $e->getMessage())
            );
        }

        return $this->redirectReferer();
    }
}
<?php
namespace Mirakl\Mci\Controller\Adminhtml\Sync;

use Mirakl\Connector\Controller\Adminhtml\AbstractSync;
use Mirakl\Process\Model\Process;

class Hierarchies extends AbstractSync
{
    /**
     * Export operator hierarchies to Mirakl
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
                ->setName('H01 synchronization')
                ->setHelper(\Mirakl\Mci\Helper\Hierarchy::class)
                ->setMethod('exportAll');

            $this->processResourceFactory->create()->save($process);
            $this->connectorConfig->setSyncDate('hierarchies');
            $this->messageManager->addSuccessMessage(__('Catalog categories will be exported asynchronously.'));
            $this->addRawSuccessMessage(__('Click <a href="%1">here</a> to view process output.', $process->getUrl()));

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while exporting Catalog categories (%1).', $e->getMessage())
            );
        }

        return $this->redirectReferer();
    }
}
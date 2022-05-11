<?php
namespace Mirakl\Mci\Controller\Adminhtml\Sync;

use Mirakl\Connector\Controller\Adminhtml\AbstractSync;
use Mirakl\Process\Model\Process;

class Attributes extends AbstractSync
{
    /**
     * Export operator attributes to Mirakl
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
                ->setName('PM01 synchronization')
                ->setHelper(\Mirakl\Mci\Helper\Attribute::class)
                ->setMethod('exportAll');

            $this->processResourceFactory->create()->save($process);
            $this->connectorConfig->setSyncDate('attributes');
            $this->messageManager->addSuccessMessage(__('Attributes will be exported asynchronously.'));
            $this->addRawSuccessMessage(__('Click <a href="%1">here</a> to view process output.', $process->getUrl()));

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while exporting attributes (%1).', $e->getMessage())
            );
        }

        return $this->redirectReferer();
    }
}
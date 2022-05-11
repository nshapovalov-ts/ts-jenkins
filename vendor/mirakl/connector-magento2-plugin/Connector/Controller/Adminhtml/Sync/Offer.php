<?php
namespace Mirakl\Connector\Controller\Adminhtml\Sync;

use Mirakl\Connector\Controller\Adminhtml\AbstractSync;
use Mirakl\Process\Model\Process;

class Offer extends AbstractSync
{
    /**
     * Synchronize Mirakl offers into Magento
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
                ->setName('OF51 synchronization')
                ->setHelper(\Mirakl\Connector\Helper\Offer\Import::class)
                ->setMethod('run');

            $this->processResourceFactory->create()->save($process);
            $this->messageManager->addSuccessMessage(__('Offers will be imported asynchronously.'));
            $this->addRawSuccessMessage(__('Click <a href="%1">here</a> to view process output.', $process->getUrl()));

        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(
                __('An error occurred while synchronizing offers (%1).', $e->getMessage())
            );
        }

        return $this->redirectReferer();
    }
}

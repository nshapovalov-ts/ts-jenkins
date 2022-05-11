<?php
namespace Mirakl\Connector\Controller\Adminhtml\Sync;

use Mirakl\Connector\Controller\Adminhtml\AbstractSync;
use Mirakl\Process\Model\Process;

class Shop extends AbstractSync
{
    /**
     * Synchronize Mirakl shops into Magento
     */
    public function execute()
    {
        try {
            if (!$this->checkConnectorEnabled()) {
                return $this->redirectReferer();
            }

            $since = $this->connectorConfig->getSyncDate('shops');
            $this->connectorConfig->setSyncDate('shops');

            $process = $this->processFactory->create()
                ->setType(Process::TYPE_ADMIN)
                ->setName('S20 synchronization')
                ->setHelper(\Mirakl\Connector\Helper\Shop::class)
                ->setMethod('synchronize')
                ->setParams([$since]);

            $this->processResourceFactory->create()->save($process);
            $this->messageManager->addSuccessMessage(__('Shops will be imported asynchronously.'));
            $this->addRawSuccessMessage(__('Click <a href="%1">here</a> to view process output.', $process->getUrl()));

        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(
                __('An error occurred while synchronizing shops (%1).', $e->getMessage())
            );
        }

        return $this->redirectReferer();
    }
}

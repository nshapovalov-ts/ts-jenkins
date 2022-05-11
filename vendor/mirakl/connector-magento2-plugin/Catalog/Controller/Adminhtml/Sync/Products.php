<?php
namespace Mirakl\Catalog\Controller\Adminhtml\Sync;

use Mirakl\Connector\Controller\Adminhtml\AbstractSync;
use Mirakl\Process\Model\Process;

class Products extends AbstractSync
{
    /**
     * Synchronize products
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
                ->setName('P21 synchronization')
                ->setHelper(\Mirakl\Catalog\Helper\Product::class)
                ->setMethod('exportAll');

            $this->processResourceFactory->create()->save($process);
            $this->connectorConfig->setSyncDate('products');
            $this->messageManager->addSuccessMessage(__('Products will be exported asynchronously.'));
            $this->addRawSuccessMessage(__('Click <a href="%1">here</a> to view process output.', $process->getUrl()));

        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(
                __('An error occurred while exporting products (%1).', $e->getMessage())
            );
        }

        return $this->redirectReferer();
    }
}

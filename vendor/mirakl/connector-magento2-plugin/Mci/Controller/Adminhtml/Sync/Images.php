<?php
namespace Mirakl\Mci\Controller\Adminhtml\Sync;

use Mirakl\Connector\Controller\Adminhtml\AbstractSync;
use Mirakl\Process\Model\Process;

class Images extends AbstractSync
{
    /**
     * Download and import pending products images into Magento
     */
    public function execute()
    {
        try {
            /** @var \Mirakl\Mci\Helper\Config $mciConfig */
            $mciConfig = $this->_objectManager->get(\Mirakl\Mci\Helper\Config::class);
            $limit = $mciConfig->getImagesImportLimit();

            $process = $this->processFactory->create()
                ->setType(Process::TYPE_ADMIN)
                ->setName('Products images import')
                ->setHelper(\Mirakl\Mci\Helper\Product\Image::class)
                ->setMethod('run')
                ->setParams([$limit]);

            $this->processResourceFactory->create()->save($process);

            $this->messageManager->addSuccessMessage(
                __('Images will be downloaded and imported asynchronously.')
            );
            $this->addRawSuccessMessage(
                __('Click <a href="%1">here</a> to view process output.', $process->getUrl())
            );

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while importing images (%1).', $e->getMessage())
            );
        }

        return $this->redirectReferer();
    }
}
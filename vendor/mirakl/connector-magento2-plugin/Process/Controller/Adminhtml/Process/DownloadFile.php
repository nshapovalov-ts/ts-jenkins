<?php
namespace Mirakl\Process\Controller\Adminhtml\Process;

/**
 * @method \Magento\Framework\App\Response\Http getResponse()
 */
class DownloadFile extends AbstractProcessAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $process = $this->getProcess();

        if (!$process->getId()) {
            return $this->redirectError(__('This process no longer exists.'));
        }

        $file = $this->getRequest()->getParam('mirakl', false) ? $process->getMiraklFile() : $process->getFile();
        if (!$file) {
            return $this->redirectError(__('File does not exist.'), true);
        }

        $fileName = pathinfo($file, PATHINFO_BASENAME);

        $this->getResponse()->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0',true)
            ->setHeader('Content-type', 'application/octet-stream', true)
            ->setHeader('Content-Length', filesize($file))
            ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);

        $this->getResponse()->clearBody();
        $this->getResponse()->sendHeaders();

        $this->_session->writeClose();
        echo file_get_contents($file);
    }
}

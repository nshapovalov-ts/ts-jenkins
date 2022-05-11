<?php
namespace Mirakl\Process\Controller\Adminhtml\Process;

class Async extends AbstractProcessAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        ob_start();

        /** @var \Mirakl\Process\Helper\Data $helper */
        $helper = $this->_objectManager->create(\Mirakl\Process\Helper\Data::class);

        /** @var \Mirakl\Process\Helper\Config $config */
        $config = $this->_objectManager->create(\Mirakl\Process\Helper\Config::class);

        $body = [];
        $process = null;
        if ($config->isAutoAsyncExecution()) {
            $process = $helper->getPendingProcess();
            $body[] = $process ? __('Processing #%1', $process->getId()) : __('Nothing to process asynchronously');
        } else {
            $body[] = __('Automatic process execution is disabled');
        }

        if ($delay = $config->getTimeoutDelay()) {
            try {
                $updated = $this->getProcessResource()->markAsTimeout($delay);
                $body[] = __('%1 process%2 in timeout', $updated, $updated > 1 ? 'es' : '');
            } catch (\Exception $e) {
                $body[] = $e->getMessage();
            }
        }

        $this->getResponse()
            ->setBody(implode(' / ', $body))
            ->sendResponse();

        session_write_close();
        ob_end_flush();
        flush();

        if ($process) {
            $process->run();
        }

        exit; // Nothing more to do
    }
}

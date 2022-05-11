<?php
namespace Mirakl\Process\Helper;

class Config extends \Mirakl\Core\Helper\Config
{
    const XML_PATH_AUTO_ASYNC_EXECUTION  = 'mirakl_process/general/auto_async_execution';
    const XML_PATH_SHOW_FILE_MAX_SIZE    = 'mirakl_process/general/show_file_max_size';
    const XML_PATH_PROCESS_TIMEOUT_DELAY = 'mirakl_process/general/timeout_delay';

    /**
     * Returns allowed max file size (in MB) for process files that can be viewed directly in browser
     *
     * @return  int
     */
    public function getShowFileMaxSize()
    {
        return $this->getValue(self::XML_PATH_SHOW_FILE_MAX_SIZE);
    }

    /**
     * Returns delay in minutes after which a process has to be automatically cancelled (blank = no timeout).
     *
     * @return  int
     */
    public function getTimeoutDelay()
    {
        return $this->getValue(self::XML_PATH_PROCESS_TIMEOUT_DELAY);
    }

    /**
     * Returns true if processes can be executed automatically
     * through an AJAX request in Magento admin, false otherwise.
     *
     * @return  bool
     */
    public function isAutoAsyncExecution()
    {
        return $this->getValue(self::XML_PATH_AUTO_ASYNC_EXECUTION);
    }
}

<?php
namespace Mirakl\Process\Model\Output;

use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Process\Model\Process;
use Psr\Log\LoggerInterface;

abstract class AbstractOutput implements OutputInterface
{
    /**
     * @var Process
     */
    protected $process;

    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    abstract public function display($str);

    /**
     * @param   CoreHelper      $coreHelper
     * @param   Process         $process
     * @param   LoggerInterface $logger
     */
    public function __construct(CoreHelper $coreHelper, Process $process, LoggerInterface $logger)
    {
        $this->coreHelper = $coreHelper;
        $this->process = $process;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->display(__('Memory Peak Usage: %1', $this->coreHelper->formatSize(memory_get_peak_usage(true))));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        $class = get_class($this);

        return strtolower(substr($class, strrpos($class, '\\') + 1));
    }
}

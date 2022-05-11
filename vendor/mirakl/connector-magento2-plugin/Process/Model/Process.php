<?php
namespace Mirakl\Process\Model;

use Magento\Framework\Data\Collection\AbstractDb as AbstractDbCollection;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Mirakl\Api\Helper\SynchroResultInterface;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Process\Helper\Config as ProcessConfig;
use Mirakl\Process\Helper\Data as ProcessHelper;
use Mirakl\Process\Model\Exception\AlreadyRunningException;
use Mirakl\Process\Model\Output\Factory as OutputFactory;
use Mirakl\Process\Model\Output\OutputInterface;
use Mirakl\Process\Model\ResourceModel\Process as ProcessResource;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;

/**
 * @method  string  getCreatedAt()
 * @method  $this   setCreatedAt(string $createdAt)
 * @method  $this   setDuration(int $duration)
 * @method  string  getErrorReport()
 * @method  $this   setErrorReport(string $report)
 * @method  string  getFile()
 * @method  $this   setFile(string $file)
 * @method  string  getHash()
 * @method  $this   setHash(string $hash)
 * @method  string  getHelper()
 * @method  $this   setHelper(string $helper)
 * @method  string  getMethod()
 * @method  $this   setMethod(string $method)
 * @method  string  getMiraklFile()
 * @method  $this   setMiraklFile(string $file)
 * @method  string  getMiraklStatus()
 * @method  $this   setMiraklStatus(string $status)
 * @method  string  getSuccessReport()
 * @method  $this   setSuccessReport(string $report)
 * @method  string  getSynchroId()
 * @method  $this   setSynchroId(string $synchroId)
 * @method  string  getMiraklType()
 * @method  $this   setMiraklType(string $type)
 * @method  string  getName()
 * @method  $this   setName(string $name)
 * @method  string  getOutput()
 * @method  $this   setOutput(string $output)
 * @method  $this   setParams(string|array $params)
 * @method  bool    getQuiet()
 * @method  $this   setQuiet(bool $flag)
 * @method  string  getStatus()
 * @method  $this   setStatus(string $status)
 * @method  string  getType()
 * @method  $this   setType(string $type)
 * @method  string  getUpdatedAt()
 * @method  $this   setUpdatedAt(string $updatedAt)
 */
class Process extends AbstractModel
{
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_IDLE       = 'idle';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_STOPPED    = 'stopped';
    const STATUS_TIMEOUT    = 'timeout';
    const STATUS_ERROR      = 'error';

    const TYPE_API          = 'API';
    const TYPE_CLI          = 'CLI';
    const TYPE_ADMIN        = 'ADMIN';
    const TYPE_IMPORT       = 'IMPORT';
    const TYPE_IMPORT_MCM   = 'IMPORT_MCM';

    /**
     * @var string
     */
    protected $_eventPrefix = 'mirakl_process';

    /**
     * @var string
     */
    protected $_eventObject = 'process';

    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var OutputInterface[]
     */
    protected $outputs = [];

    /**
     * @var float
     */
    protected $startedAt;

    /**
     * @var bool
     */
    protected $stopped = false;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var CoreHelper
     */
    private $coreHelper;

    /**
     * @var ProcessHelper
     */
    private $processHelper;

    /**
     * @var ProcessConfig
     */
    private $processConfig;

    /**
     * @var ProcessResource
     */
    private $processResource;

    /**
     * @var OutputFactory
     */
    private $outputFactory;

    /**
     * @param   Context                     $context
     * @param   Registry                    $registry
     * @param   AbstractResource|null       $resource
     * @param   AbstractDbCollection|null   $resourceCollection
     * @param   ObjectManagerInterface      $objectManager
     * @param   UrlInterface                $urlBuilder
     * @param   CoreHelper                  $coreHelper
     * @param   ProcessHelper               $processHelper
     * @param   ProcessConfig               $processConfig
     * @param   ProcessResourceFactory      $processResourceFactory
     * @param   OutputFactory               $outputFactory
     * @param   array                       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ObjectManagerInterface $objectManager,
        UrlInterface $urlBuilder,
        CoreHelper $coreHelper,
        ProcessHelper $processHelper,
        ProcessConfig $processConfig,
        ProcessResourceFactory $processResourceFactory,
        OutputFactory $outputFactory,
        AbstractResource $resource = null,
        AbstractDbCollection $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->objectManager   = $objectManager;
        $this->urlBuilder      = $urlBuilder;
        $this->coreHelper      = $coreHelper;
        $this->processHelper   = $processHelper;
        $this->processConfig   = $processConfig;
        $this->processResource = $processResourceFactory->create();
        $this->outputFactory   = $outputFactory;
    }

    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Process::class);

        register_shutdown_function(function() {
            if (!$this->stopped) {
                $error = error_get_last();
                if (!empty($error) && $error['type'] != E_NOTICE) {
                    $message = sprintf('%s in %s on line %d', $error['message'], $error['file'], $error['line']);
                    $this->fail($message);
                }
            }
        });
    }

    /**
     * Stops current process execution on shutdown
     */
    public function __destruct()
    {
        if (!$this->stopped && ($output = ob_get_contents())) {
            $this->output($output);
        }
        $this->stop();
    }

    /**
     * @param   string|OutputInterface  $output
     * @return  $this
     * @throws  \Exception
     */
    public function addOutput($output)
    {
        if (is_string($output)) {
            $output = $this->outputFactory->create($output, $this);
        }

        if (!$output instanceof OutputInterface) {
            throw new \Exception('Invalid output specified.');
        }

        $this->outputs[$output->getType()] = $output;

        return $this;
    }

    /**
     * Returns true if we can check Mirakl API status on process
     *
     * @return  bool
     */
    public function canCheckMiraklStatus()
    {
        return !$this->isProcessing() &&
            ($this->getMiraklStatus() == self::STATUS_PENDING || $this->getMiraklStatus() == self::STATUS_PROCESSING);
    }

    /**
     * Returns true if process can be ran
     *
     * @return  bool
     */
    public function canRun()
    {
        return !$this->isProcessing() && !$this->isStatusIdle() && $this->getHelper() && $this->getMethod();
    }

    /**
     * @param   bool    $isMirakl
     * @return  bool
     */
    public function canShowFile($isMirakl = false)
    {
        $fileSize = $this->getFileSize($isMirakl);

        return $fileSize <= ($this->processConfig->getShowFileMaxSize() * 1024 * 1024); // less than 5 MB
    }

    /**
     * Returns true if process can be set to STOPPED status
     *
     * @return  bool
     */
    public function canStop()
    {
        return $this->isProcessing();
    }

    /**
     * @return  $this
     */
    public function checkMiraklStatus()
    {
        $this->addOutput('db');
        $this->output('Checking Mirakl report status...');

        try {
            $synchroId = $this->getSynchroId();

            if (empty($synchroId)) {
                return $this->output('No synchro id found for current process', true);
            }

            $this->setMiraklStatus(self::STATUS_PROCESSING);
            $this->processResource->save($this);

            $this->output(sprintf('API Synchro Id: #%s', $synchroId));

            $helper = $this->getHelperInstance();

            if (!$helper instanceof SynchroResultInterface) {
                return $this->output('Helper does not implement SynchroResultInterface', true);
            }

            // Check if complete
            $synchroResult = $helper->getSynchroResult($synchroId);

            // Not finished yet
            if ($synchroResult->getStatus() != 'COMPLETE') {
                $this->setMiraklStatus(self::STATUS_PENDING);

                return $this->output('API call is not finished ... try again later', true);
            }

            if ($synchroResult->getData('has_report')) {
                $reportFile = $helper->getErrorReport($synchroId);
                $hasError = new DataObject(['error' => false]);
                if ($filepath = $this->processHelper->saveFile($reportFile, 'json')) {
                    $this->setMiraklFile($filepath);
                    // Send an event to check if there is an error in report file
                    $this->_eventManager->dispatch('mirakl_api_get_synchronization_report', [
                        'report_file_path' => $filepath,
                        'has_error' => $hasError
                    ]);
                }

                if ($hasError->getData('error') === true) {
                    $this->output('Status ERROR');
                    $this->setMiraklStatus(self::STATUS_ERROR);
                } else {
                    $this->output('Status COMPLETED');
                    $this->setMiraklStatus(self::STATUS_COMPLETED);
                }
            } elseif ($synchroResult->getErrorReport()) {
                $reportFile = $helper->getErrorReport($synchroId);
                $this->output('Status ERROR');

                if ($filepath = $this->processHelper->saveFile($reportFile)) {
                    $fileSize = $this->coreHelper->formatSize(filesize($filepath));
                    $this->setMiraklFile($filepath);
                    $this->output(__('Error file has been saved as "%1" (%2)', basename($filepath), $fileSize));
                }

                $this->setMiraklStatus(self::STATUS_ERROR);
            } else {
                $this->output('Status SUCCESS');
                $this->setMiraklStatus(self::STATUS_COMPLETED);
            }
        } catch (\Exception $e) {
            $this->output(sprintf(
                'Check report in Mirakl failed: %s',
                $e->getMessage()
            ));
            $this->setMiraklStatus(self::STATUS_ERROR);
        }

        $this->processResource->save($this);

        return $this;
    }

    /**
     * Calls current process helper->method()
     *
     * @throws  AlreadyRunningException
     * @throws  \InvalidArgumentException
     */
    public function execute()
    {
        try {
            if ($this->isProcessing()) {
                throw new AlreadyRunningException('Process is already running. Please try again later.');
            }

            $this->setStatus(self::STATUS_PROCESSING);
            $helper = $this->getHelperInstance();
            $method = $this->getMethod();

            if (!method_exists($helper, $method)) {
                throw new \InvalidArgumentException("Invalid helper method specified '$method'");
            }

            $this->output(__('Running %1::%2()', get_class($helper), $method), true);
            $args = [$this];
            if ($this->getParams()) {
                $args = array_merge($args, $this->getParams());
            }

            ob_start();

            call_user_func_array([$helper, $method], $args);

        } catch (\Exception $e) {
            $this->fail($e->getMessage());
            $this->_logger->critical($e->getMessage());
            throw $e;
        } finally {
            if ($output = ob_get_clean()) {
                $this->output($output);
            }
        }
    }

    /**
     * Marks current process as failed and stops execution
     *
     * @param   string|null $message
     * @return  $this
     */
    public function fail($message = null)
    {
        if ($message) {
            $this->output($message);
        }

        return $this->stop(self::STATUS_ERROR);
    }

    /**
     * @return  int|\DateInterval
     */
    public function getDuration()
    {
        $duration = $this->_getData('duration');
        if (!$duration) {
            if ($this->isProcessing() || $this->isStatusIdle()) {
                $start = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getCreatedAt());
                $duration = $start->diff(new \DateTime());
            } elseif ($this->isEnded()){
                $start = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getCreatedAt());
                $end = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getUpdatedAt());
                $duration = $start->diff($end);
            }
        }

        return $duration;
    }

    /**
     * Returns file size in bytes
     *
     * @param   bool    $isMirakl
     * @return  bool|int
     */
    public function getFileSize($isMirakl = false)
    {
        $filePath = $isMirakl ? $this->getMiraklFile() : $this->getFile();

        if (strlen($filePath) && is_file($filePath)) {
            return filesize($filePath);
        }

        return false;
    }

    /**
     * Returns process file download URL for admin
     *
     * @param   bool    $isMirakl
     * @return  string|false
     */
    public function getDownloadFileUrl($isMirakl = false)
    {
        $file = $isMirakl ? $this->getMiraklFile() : $this->getFile();

        if (!$file || !file_exists($file)) {
            return false;
        }

        return $this->urlBuilder->getUrl('mirakl/process/downloadFile', [
            'id' => $this->getId(),
            'mirakl' => $isMirakl,
        ]);
    }

    /**
     * Returns file size formatted
     *
     * @param   string  $separator
     * @param   bool    $isMirakl
     * @return  string|false
     */
    public function getFileSizeFormatted($separator = ' ', $isMirakl = false)
    {
        if ($fileSize = $this->getFileSize($isMirakl)) {
            return $this->coreHelper->formatSize($fileSize, $separator);
        }

        return false;
    }

    /**
     * @param   bool    $isMirakl
     * @return  string|false
     */
    public function getFileUrl($isMirakl = false)
    {
        $file = $isMirakl ? $this->getMiraklFile() : $this->getFile();

        if (!$file || !file_exists($file)) {
            return false;
        }

        return $this->processHelper->getFileUrl($file);
    }

    /**
     * @return  mixed
     * @throws  \InvalidArgumentException
     */
    private function getHelperInstance()
    {
        $name = $this->getHelper();
        if (!class_exists($name)) {
            throw new \InvalidArgumentException("Invalid helper specified '$name'");
        }

        return $this->objectManager->create($name);
    }

    /**
     * @return  array
     */
    public function getParams()
    {
        $params = $this->_getData('params');
        if (is_string($params)) {
            $params = unserialize($params);
        }

        return is_array($params) ? $params : [];
    }

    /**
     * @param   null|string
     * @return  array|string
     */
    public static function getStatuses()
    {
        static $statuses;
        if (!$statuses) {
            $class = new \ReflectionClass(__CLASS__);
            foreach ($class->getConstants() as $name => $value) {
                if (0 === strpos($name, 'STATUS_')) {
                    $statuses[$value] = $value;
                }
            }
        }

        return $statuses;
    }

    /**
     * @param   bool    $isMirakl
     * @return  string
     */
    public function getStatusClass($isMirakl = false)
    {
        $status = $isMirakl ? $this->getMiraklStatus() : $this->getStatus();

        switch ($status) {
            case self::STATUS_PENDING:
            case self::STATUS_IDLE:
                $class = 'grid-severity-minor';
                break;
            case self::STATUS_PROCESSING:
                $class = 'grid-severity-major';
                break;
            case self::STATUS_STOPPED:
            case self::STATUS_ERROR:
            case self::STATUS_TIMEOUT:
                $class = 'grid-severity-critical';
                break;
            case self::STATUS_COMPLETED:
            default:
                $class = 'grid-severity-notice';
        }

        return $class;
    }

    /**
     * Returns process URL for admin
     *
     * @return  string
     */
    public function getUrl()
    {
        return $this->urlBuilder->getUrl('mirakl/process/view', [
            'id' => $this->getId()
        ]);
    }

    /**
     * Sets current process status to idle
     *
     * @return  $this
     */
    public function idle()
    {
        return $this->setStatus(self::STATUS_IDLE);
    }

    /**
     * @return  bool
     */
    public function isCompleted()
    {
        return $this->getStatus() === self::STATUS_COMPLETED;
    }

    /**
     * @return  bool
     */
    public function isEnded()
    {
        return $this->isCompleted() || $this->isStopped() || $this->isTimeout() || $this->isError();
    }

    /**
     * @return  bool
     */
    public function isError()
    {
        return $this->getStatus() === self::STATUS_ERROR;
    }

    /**
     * @return  bool
     */
    public function isPending()
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }

    /**
     * @return  bool
     */
    public function isProcessing()
    {
        return $this->getStatus() === self::STATUS_PROCESSING;
    }

    /**
     * @return  bool
     */
    public function isStatusIdle()
    {
        return $this->getStatus() === self::STATUS_IDLE;
    }

    /**
     * @return  bool
     */
    public function isStopped()
    {
        return $this->getStatus() === self::STATUS_STOPPED;
    }

    /**
     * @return  bool
     */
    public function isTimeout()
    {
        return $this->getStatus() === self::STATUS_TIMEOUT;
    }

    /**
     * Outputs specified string in all associated output handlers
     *
     * @param   string  $str
     * @param   bool    $save
     * @return  $this
     */
    public function output($str, $save = false)
    {
        foreach ($this->outputs as $output) {
            $output->display($str);
        }

        if ($save) {
            $this->processResource->save($this);
        }

        return $this;
    }

    /**
     * Wraps process execution
     *
     * @param   bool    $force
     * @return  $this
     */
    public function run($force = false)
    {
        if ($this->isPending() || $force) {
            $this->start();
            $this->execute();
            $this->stop();
        }

        return $this;
    }

    /**
     * Starts current process
     *
     * @return  $this
     */
    public function start()
    {
        if (!$this->startedAt) {
            $this->startedAt = microtime(true);
            $this->setCreatedAt(time())
                ->addOutput('db')
                ->setOutput(null)
                ->setDuration(null);

            if (PHP_SAPI == 'cli') {
                $this->addOutput('cli');
            }

            $this->processResource->save($this);
        }

        return $this;
    }

    /**
     * Stops current process
     *
     * @param   string  $status
     * @return  $this
     */
    public function stop($status = self::STATUS_COMPLETED)
    {
        if (!$this->stopped) {
            $this->updateDuration();

            foreach ($this->outputs as $output) {
                $output->close();
            }

            $this->stopped = true;
            $this->setStatus($status);
        }

        if ($this->startedAt) {
            $this->processResource->save($this);
        }

        return $this;
    }

    /**
     * Updates current process duration
     *
     * @return  $this
     */
    public function updateDuration()
    {
        if ($this->startedAt) {
            $duration = ceil(microtime(true) - $this->startedAt);
            $this->setDuration($duration);
        } elseif ($this->getCreatedAt()) {
            $start = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getCreatedAt());
            $duration = (new \DateTime())->getTimestamp() - $start->getTimestamp();
            $this->setDuration(max(1, $duration)); // 1 second minimum
        }

        return $this;
    }
}

<?php
namespace Mirakl\Process\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Process\Model\Process;
use Mirakl\Process\Model\ProcessFactory;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ResourceFactory;
use Mirakl\Process\Model\ResourceModel\Process\Collection;
use Mirakl\Process\Model\ResourceModel\Process\CollectionFactory;

class Data extends AbstractHelper
{
    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @param   Context             $context
     * @param   CoreHelper          $coreHelper
     * @param   Config              $config
     * @param   CollectionFactory   $collectionFactory
     * @param   Filesystem          $filesystem
     * @param   ProcessFactory      $processFactory
     * @param   ResourceFactory     $processFactory
     */
    public function __construct(
        Context $context,
        CoreHelper $coreHelper,
        Config $config,
        CollectionFactory $collectionFactory,
        Filesystem $filesystem,
        ProcessFactory $processFactory,
        ResourceFactory $resourceFactory
    ) {
        parent::__construct($context);
        $this->coreHelper = $coreHelper;
        $this->config = $config;
        $this->collectionFactory = $collectionFactory;
        $this->filesystem = $filesystem;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->processFactory = $processFactory;
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @return  string|false
     */
    public function getArchiveDir()
    {
        $path = implode(DIRECTORY_SEPARATOR, ['mirakl', 'process', date('Y'), date('m'), date('d')]);
        $this->mediaDirectory->create($path);
        if (!$this->mediaDirectory->isWritable($path)) {
            return false;
        }

        return $this->mediaDirectory->getAbsolutePath($path);
    }

    /**
     * Returns URL to the specified file
     *
     * @param   string  $filePath
     * @return  string
     */
    public function getFileUrl($filePath)
    {
        $relativePath = $this->getRelativePath($filePath);
        $baseUrl = $this->coreHelper->getBaseUrl();

        return $baseUrl . $relativePath;
    }

    /**
     * Removes base dir from specified file path
     *
     * @param   string  $filePath
     * @return  string
     */
    public function getRelativePath($filePath)
    {
        return trim(str_replace(BP, '', $filePath), DIRECTORY_SEPARATOR);
    }

    /**
     * Returns the processes for which we want to check the Mirakl status
     *
     * @return  Collection
     */
    public function getMiraklStatusToCheckProcesses()
    {
        // Retrieve processing processes to exclude them afterwards
        $processing = $this->collectionFactory->create()
            ->addProcessingFilter();

        // Retrieve completed processes
        $completed = $this->collectionFactory->create()
            ->addCompletedFilter()
            ->addMiraklPendingFilter()
            ->addApiTypeFilter()
            ->addExcludeHashFilter($processing->getColumnValues('hash'))
            ->setOrder('id', 'ASC'); // oldest first

        return $completed;
    }

    /**
     * Returns the older pending process
     *
     * @return  Process|null
     */
    public function getPendingProcess()
    {
        $process = null;

        // Retrieve processing processes
        $processing = $this->collectionFactory->create()
            ->addProcessingFilter();

        // Retrieve pending processes
        $pending = $this->collectionFactory->create()
            ->addPendingFilter()
            ->addExcludeHashFilter($processing->getColumnValues('hash'))
            ->setOrder('id', 'ASC'); // oldest first

        $pending->getSelect()->limit(1);

        if ($pending->count()) {
            /** @var Process $process */
            $process = $pending->getFirstItem();
        }

        return $process;
    }

    /**
     * Archives specified file in media/ folder
     *
     * @param   string|\SplFileObject|FileWrapper   $file
     * @param   string|null                         $extension
     * @return  string|false
     */
    public function saveFile($file, $extension = null)
    {
        if (!$path = $this->getArchiveDir()) {
            return false;
        }

        if (is_string($file)) {
            $file = new \SplFileObject($file, 'r');
        }

        if ($file instanceof FileWrapper) {
            $file = $file->getFile();
        }

        if (null === $extension) {
            $extension = $file->getFlags() & \SplFileObject::READ_CSV ? 'csv' : 'txt';
        }

        list ($micro, $time) = explode(' ', microtime());
        $filename = sprintf('%s_%s.%s', date('Ymd_His', $time), $micro, $extension);
        $filepath = $path . DIRECTORY_SEPARATOR . $filename;

        if (!$fh = @fopen($filepath, 'w+')) {
            return false;
        }

        $file->rewind();
        while (!$file->eof()) {
            fwrite($fh, $file->fgets());
        }
        fclose($fh);

        return $filepath;
    }

    /**
     * @param   string|null $helper
     * @param   string|null $method
     * @return  Collection
     * @throws  \Exception
     */
    public function checkProcessingProcess($helper = null, $method = null)
    {
        $processing = $this->collectionFactory->create()->addProcessingFilter();
        if ($helper) {
            $processing->addFieldToFilter('helper', $helper);
            if ($method) {
                $processing->addFieldToFilter('method', $method);
            }
        }

        $delay = $this->config->getTimeoutDelay();
        $delay = is_int($delay) ? (int) $delay : 0;

        if ($delay <= 0) {
            return $processing;
        }

        $timoutDate = new \DateTime();
        $timoutDate->sub(new \DateInterval(sprintf('P%di', $delay)));
        foreach ($processing as $process) {
            /** @var Process $process */
            $updatedAt = new \DateTime($process->getUpdatedAt());
            if ($updatedAt < $timoutDate) {
                $process->setStatus(Process::STATUS_TIMEOUT);
                $this->resourceFactory->create()->save($process);
            }
        }

        return $processing;
    }
}

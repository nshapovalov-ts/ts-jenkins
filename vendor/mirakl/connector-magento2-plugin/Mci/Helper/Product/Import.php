<?php
namespace Mirakl\Mci\Helper\Product;

use Magento\Framework\ObjectManagerInterface;
use Mirakl\Api\Helper\ProductImport as Api;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MCI\Common\Domain\Product\ProductImportStatus;
use Mirakl\Mci\Helper\Config;
use Mirakl\Mci\Model\Product\Import\Handler;
use Mirakl\Process\Model\Process;
use Mirakl\Process\Model\ProcessFactory;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;

class Import
{
    const IMPORT_PATH_MAX_FILES = 2;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    /**
     * @param   ObjectManagerInterface  $objectManager
     * @param   Config                  $config
     * @param   Api                     $api
     * @param   ProcessFactory          $processFactory
     * @param   ProcessResourceFactory  $processResourceFactory
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Config $config,
        Api $api,
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory
    ) {
        $this->objectManager = $objectManager;
        $this->config = $config;
        $this->api = $api;
        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
    }

    /**
     * Imports products into Magento from specified process
     *
     * @param   Process     $process
     * @param   string      $shopId
     * @param   int|null    $importId
     * @return  void
     */
    public function runFile(Process $process, $shopId, $importId = null)
    {
        /** @var Handler\Csv $handler */
        $handler = $this->objectManager->create(Handler\Csv::class);

        $handler->run($shopId, $process->getFile(), $process);

        if ($importId && $this->config->isSendImportReport()) {
            // Create process for P43 (asynchronous)
            $process->output(__('Creating process for P43 import report...'));
            $newProcess = $this->createImportReportProcess($importId, $process);
            $process->output(__('Done! (%1)', $newProcess->getId()));
        }
    }

    /**
     * Imports CSV files from specified path
     *
     * @param   string  $path
     * @param   int     $maxFiles
     * @return  void
     */
    public function runPath($path, $maxFiles = self::IMPORT_PATH_MAX_FILES)
    {
        /** @var Handler\Path $handler */
        $handler = $this->objectManager->create(Handler\Path::class);

        $handler->run($path, $maxFiles);
    }

    /**
     * Creates process that will send products import report to Mirakl (P43)
     *
     * @param   int     $importId
     * @param   Process $process
     * @return  Process
     */
    private function createImportReportProcess($importId, Process $process)
    {
        $status = $process->isError() ? ProductImportStatus::FAILED : ProductImportStatus::COMPLETE;

        $newProcess = $this->processFactory->create()
            ->setType(Process::TYPE_API)
            ->setName('P43 MCI products import report')
            ->setStatus(Process::STATUS_PENDING)
            ->setHelper(__CLASS__)
            ->setMethod('sendImportReport')
            ->setParams([$importId, $process->getSuccessReport(), $process->getErrorReport(), $status]);

        $this->processResourceFactory->create()->save($newProcess);

        return $newProcess;
    }

    /**
     * (P43) Sends products import report
     *
     * @param   Process $process
     * @param   int     $importId
     * @param   string  $successReport
     * @param   string  $errorReport
     * @param   string  $status
     * @return  void
     */
    public function sendImportReport(Process $process, $importId, $successReport, $errorReport, $status)
    {
        $process
            ->output(__('Sending products import report files and status to Mirakl...'))
            ->output(__('Import Id: %1', $importId))
            ->output(__('Status: %1', $status), true);

        // Successes report file
        $productsFile = null;
        if ($successReport) {
            $productsFile = $this->getReportFile($successReport)
                ->setFileName("import_report_products_{$importId}.csv")
                ->setFileExtension('csv');
        }

        // Errors report file
        $errorsFile = null;
        if ($errorReport) {
            $errorsFile = $this->getReportFile($errorReport)
                ->setFileName("import_report_errors_{$importId}.csv")
                ->setFileExtension('csv');
        }

        $this->api->sendProductsImportReport($importId, $productsFile, $errorsFile, $status);

        $process->output(__('Done!'));
    }

    /**
     * @param   string  $report
     * @return  FileWrapper
     */
    protected function getReportFile($report)
    {
        if (strlen($report) > 4000 || strpos($report, 'mirakl_report_') === false) {
            // Retro-compatibility: Until 1.13.5, the file content was stored in database
            return new FileWrapper($report);
        }

        return new FileWrapper(new \SplFileObject($report));
    }
}

<?php
namespace Mirakl\Mci\Model\Product\Import\Handler;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Mirakl\Api\Helper\Config as ApiConfigHelper;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Helper\Hash as HashHelper;
use Mirakl\Mci\Model\Product\Import\Adapter\AdapterInterface;
use Mirakl\Mci\Model\Product\Import\Exception\WarningException;
use Mirakl\Mci\Model\Product\Import\Indexer\Indexer as ImportIndexer;
use Mirakl\Process\Helper\Data as ProcessHelper;
use Mirakl\Process\Model\Process;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;

class Csv
{
    use CsvTrait;

    /**
     * @var string
     */
    public $delimiter = ';';

    /**
     * @var string
     */
    public $enclosure = '"';

    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @var ApiConfigHelper
     */
    protected $apiConfigHelper;

    /**
     * @var ProcessHelper
     */
    protected $processHelper;

    /**
     * @var ProcessResourceFactory
     */
    private $processResourceFactory;

    /**
     * @var HashHelper
     */
    protected $hashHelper;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var \SplFileObject
     */
    private $successReport = null;

    /**
     * @var int
     */
    private $successLinesCount = 0;

    /**
     * @var \SplFileObject
     */
    private $errorReport = null;

    /**
     * @var int
     */
    private $errorLinesCount = 0;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ImportIndexer
     */
    protected $importIndexer;

    /**
     * @param   CoreHelper              $coreHelper
     * @param   ApiConfigHelper         $apiConfigHelper
     * @param   ProcessHelper           $processHelper
     * @param   ProcessResourceFactory  $processResourceFactory
     * @param   HashHelper              $hashHelper
     * @param   AdapterInterface        $adapter
     * @param   Registry                $registry
     * @param   ImportIndexer           $importIndexer
     */
    public function __construct(
        CoreHelper $coreHelper,
        ApiConfigHelper $apiConfigHelper,
        ProcessHelper $processHelper,
        ProcessResourceFactory $processResourceFactory,
        HashHelper $hashHelper,
        AdapterInterface $adapter,
        Registry $registry,
        ImportIndexer $importIndexer
    ) {
        $this->coreHelper             = $coreHelper;
        $this->apiConfigHelper        = $apiConfigHelper;
        $this->processHelper          = $processHelper;
        $this->processResourceFactory = $processResourceFactory;
        $this->hashHelper             = $hashHelper;
        $this->adapter                = $adapter;
        $this->registry               = $registry;
        $this->importIndexer          = $importIndexer;
    }

    /**
     * @param   string  $shopId
     * @param   string  $file
     * @param   Process $process
     * @return  $this
     * @throws  \Exception
     */
    public function run($shopId, $file, Process $process)
    {
        try {
            $this->registry->register('mirakl_import_working', true);

            // Disable or not the indexing when UpdateOnSave mode
            $this->importIndexer->initIndexers();

            set_time_limit(0); // Script may take a while

            $this->apiConfigHelper->disable();

            $fh = fopen($file, 'r');
            if (!$fh) {
                throw new LocalizedException(__('Could not read file "%1"', $file));
            }

            if ($filepath = $this->processHelper->saveFile($file)) {
                $fileSize = $this->coreHelper->formatSize(filesize($filepath));
                $process->output(__('File has been saved as "%1" (%2)', basename($filepath), $fileSize));
                $process->setFile($filepath);
            }

            $process->output(__('Importing file for shop %1...', $shopId), true);

            $i = 0; // Line number
            $cols = []; // Used to map keys and values

            /** @var \Mirakl\Process\Model\ResourceModel\Process $processResource */
            $processResource = $this->processResourceFactory->create();

            $this->delimiter = $this->getValidDelimiter($fh, $this->delimiter, $this->enclosure);

            if (false === $this->delimiter) {
                $process->output(__('No valid delimiter found. Import canceled.'));

                return $this;
            }

            // Loop through CSV file
            // We used the char "\x80" as escape_char to avoid problem when we have a \ before a double quote
            while ($row = fgetcsv($fh, 0, $this->delimiter, $this->enclosure, "\x80")) {
                if (++$i === 1) {
                    $this->writeSuccessReport(array_merge($row, ['warning']));
                    $this->writeErrorReport(array_merge($row, ['error']));
                    $cols = $row; // Stores column names from first line
                    continue;
                }

                try {
                    $start = microtime(true);

                    // Combine column names with values to build an associative array
                    $data = array_combine($cols, $row);

                    if (!isset($data[MciHelper::ATTRIBUTE_SKU])) {
                        throw new \Exception(__('Could not find "%1" column in product data', MciHelper::ATTRIBUTE_SKU));
                    }

                    $sku = trim($data[MciHelper::ATTRIBUTE_SKU]);

                    if (empty($sku)) {
                        throw new \Exception(__('Column "%1" cannot be empty', MciHelper::ATTRIBUTE_SKU));
                    }

                    $hash = sha1(json_encode($data));
                    if ($this->hashHelper->isShopHashExists($shopId, $sku, $hash)) {
                        // valid product but skipping it
                        $message = __('Skipping row %1 because already imported (%2)', $i, $sku);
                        $this->writeSuccessReport(array_merge($row, [$message]));
                        $process->output($message);
                        continue;
                    }

                    $this->adapter->import($shopId, $data);

                    $time = round(microtime(true) - $start, 2);
                    $message = __('[OK] Product has been saved for line %1 (%2s)', $i, $time);
                    $this->writeSuccessReport(array_merge($row, [$message]));
                    $process->output($message);

                    if ($i % 5 === 0) {
                        $processResource->save($process);
                    }

                    $this->hashHelper->saveShopHash($shopId, $sku, $hash);

                } catch (WarningException $e) {
                    $message = __('Warning on line %1: %2', $i, $e->getMessage());
                    $this->writeSuccessReport(array_merge($row, [$message]));
                    $process->output($message);
                } catch (\Exception $e) {
                    $error = __('Error on line %1: %2', $i, $e->getMessage());
                    $this->writeErrorReport(array_merge($row, [$error]));
                    $process->output($error);
                }
            }
        } catch (\Exception $e) {
            if (isset($row)) {
                $this->writeErrorReport(array_merge($row, [$e->getMessage()]));
            }
            $process->fail($e->getMessage());
            throw $e;
        } finally {
            // Save success and error reports in process object
            if ($this->successLinesCount > 1) { // header cols are always present
                $this->successReport->fflush();
                $process->setSuccessReport($this->successReport->getRealPath());
            }
            if ($this->errorLinesCount > 1) { // header cols are always present
                $this->errorReport->fflush();
                $process->setErrorReport($this->errorReport->getRealPath());
            }

            $this->registry->unregister('mirakl_import_working');

            // Reindex
            $process->output(__('Reindexing...'), true);
            $this->importIndexer->reindex();
        }

        return $this;
    }

    /**
     * @param   string  $filename
     * @param   string  $openMode
     * @param   string  $delimiter
     * @param   string  $enclosure
     * @return  \SplFileObject
     */
    private function createReportFile($filename = '', $openMode = 'w', $delimiter = ';', $enclosure = '"')
    {
        if (!$filename) {
            $filename = tempnam(sys_get_temp_dir(), 'mirakl_report_');
        }

        $file = new \SplFileObject($filename, $openMode);
        $file->setCsvControl($delimiter, $enclosure);

        return $file;
    }

    /**
     * @param   array $data
     * @return  mixed
     */
    private function writeSuccessReport(array $data)
    {
        $this->successLinesCount++;

        if ($this->successReport === null) {
            $this->successReport = $this->createReportFile();
        }

        return $this->successReport->fputcsv($data);
    }

    /**
     * @param   array $data
     * @return  mixed
     */
    private function writeErrorReport(array $data)
    {
        $this->errorLinesCount++;

        if ($this->errorReport === null) {
            $this->errorReport = $this->createReportFile();
        }

        return $this->errorReport->fputcsv($data);
    }
}

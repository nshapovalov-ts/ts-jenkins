<?php
namespace Mirakl\Mcm\Model\Product\Import\Handler;

use Magento\Framework\Exception\LocalizedException;
use Mirakl\Api\Helper\Config as ApiConfigHelper;
use Mirakl\Api\Helper\Mcm\Product as ProductApiHelper;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Mci\Helper\Hash as HashHelper;
use Mirakl\Mci\Model\Product\Import\Exception\WarningException;
use Mirakl\Mci\Model\Product\Import\Handler\CsvTrait;
use Mirakl\Mci\Model\Product\Import\Report\ReportInterface;
use Mirakl\Mcm\Helper\Config as McmConfig;
use Mirakl\Mcm\Helper\Data as McmHelper;
use Mirakl\Mcm\Model\Product\Import\Adapter\AdapterInterface;
use Mirakl\Mcm\Model\Product\Import\Indexer\Indexer;
use Mirakl\MCM\Front\Domain\Product\Synchronization\ProductAcceptance;
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
    protected $processResourceFactory;

    /**
     * @var HashHelper
     */
    protected $hashHelper;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var ReportInterface
     */
    protected $successReport;

    /**
     * @var ReportInterface
     */
    protected $errorReport;

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @var McmConfig
     */
    protected $config;

    /**
     * @var ProductApiHelper
     */
    protected $productApiHelper;

    /**
     * @var string
     */
    protected $identifierCode;

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @param   CoreHelper              $coreHelper
     * @param   ApiConfigHelper         $apiConfigHelper
     * @param   ProcessHelper           $processHelper
     * @param   ProcessResourceFactory  $processResourceFactory
     * @param   HashHelper              $hashHelper
     * @param   AdapterInterface        $adapter
     * @param   ReportInterface         $successReport
     * @param   ReportInterface         $errorReport
     * @param   ConnectorConfig         $connectorConfig
     * @param   McmConfig               $config
     * @param   ProductApiHelper        $productApiHelper
     * @param   Indexer                 $indexer
     * @param   string                  $identifierCode
     */
    public function __construct(
        CoreHelper $coreHelper,
        ApiConfigHelper $apiConfigHelper,
        ProcessHelper $processHelper,
        ProcessResourceFactory $processResourceFactory,
        HashHelper $hashHelper,
        AdapterInterface $adapter,
        ReportInterface $successReport,
        ReportInterface $errorReport,
        ConnectorConfig $connectorConfig,
        McmConfig $config,
        ProductApiHelper $productApiHelper,
        Indexer $indexer,
        $identifierCode
    ) {
        $this->coreHelper             = $coreHelper;
        $this->apiConfigHelper        = $apiConfigHelper;
        $this->processHelper          = $processHelper;
        $this->processResourceFactory = $processResourceFactory;
        $this->hashHelper             = $hashHelper;
        $this->successReport          = $successReport;
        $this->errorReport            = $errorReport;
        $this->adapter                = $adapter;
        $this->identifierCode         = $identifierCode;
        $this->connectorConfig        = $connectorConfig;
        $this->config                 = $config;
        $this->productApiHelper       = $productApiHelper;
        $this->indexer                = $indexer;
    }

    /**
     * @param   Process         $process
     * @param   \DateTime|null  $since
     * @return  false|string
     */
    public function getApiFile(Process $process, $since)
    {
        if (!$since && ($lastSyncDate = $this->connectorConfig->getSyncDate('mcm_products_import'))) {
            $since = $lastSyncDate;
        }

        // Save last synchronization date now if file download is too long
        $this->connectorConfig->setSyncDate('mcm_products_import');

        $importParams = [];
        if ($since) {
            $process->output(__('Downloading MCM products from Mirakl to Magento since %1', $since->format('Y-m-d H:i:s')), true);
            $importParams = ['updated_since' => $since->format(\DateTime::ATOM)];
        } else {
            $process->output(__('Downloading MCM products from Mirakl to Magento'), true);
        }

        $apiFile = $this->productApiHelper->import($importParams);

        $file = null;
        if ($apiFile->fstat()['size'] > 0 && ($file = $this->processHelper->saveFile($apiFile))) {
            $fileSize = $this->coreHelper->formatSize(filesize($file));
            $process->output(__('File has been saved as "%1" (%2)', basename($file), $fileSize));
            $process->setFile($file);
        }

        return $file;
    }

    /**
     * @param   Process         $process
     * @param   \DateTime|null  $since
     * @param   bool            $sendReport
     * @return  $this
     * @throws  \Exception
     */
    public function run(Process $process, $since, $sendReport = true)
    {
        if (!$this->config->isMcmEnabled()) {
            $process->output(__('Module MCM is disabled. See your Mirakl MCM configuration'));

            return $this;
        }

        try {
            $file = $process->getFile();
            if (!$file) {
                $file = $this->getApiFile($process, $since);
            }

            if (empty($file)) {
                if ($since) {
                    $process->output(__('No products to import since %1', $since->format('Y-m-d H:i:s')));
                } else {
                    $process->output(__('No products to import'));
                }

                return $this;
            }

            $processResource = $this->processResourceFactory->create();

            set_time_limit(0); // Script may take a while

            $this->apiConfigHelper->disable();

            $fh = fopen($file, 'r');
            if (!$fh) {
                throw new LocalizedException(__('Could not read file "%1"', $file));
            }

            $process->output(__('Importing MCM file...'), true);

            // Disable or not the indexing when UpdateOnSave mode
            $this->indexer->initIndexers();

            $i = 0; // Line number
            $cols = []; // Used to map keys and values

            $this->delimiter = $this->getValidDelimiter($fh, $this->delimiter, $this->enclosure);

            if (false === $this->delimiter) {
                $process->output(__('No valid delimiter found. Import canceled.'));

                return $this;
            }

            // Loop through CSV file
            while ($row = fgetcsv($fh, 0, $this->delimiter, $this->enclosure)) {
                if (++$i === 1) {
                    $cols = $row; // Stores column names from first line
                    continue;
                }

                try {
                    $start = microtime(true);

                    // Combine column names with values to build an associative array
                    $data = array_combine($cols, $row);

                    if (!isset($data[McmHelper::CSV_MIRAKL_PRODUCT_ID])) {
                        throw new \Exception(
                            __('Could not find "%1" column in product data', McmHelper::CSV_MIRAKL_PRODUCT_ID)
                        );
                    }

                    if (!isset($data[McmHelper::CSV_MIRAKL_PRODUCT_SKU])) {
                        throw new \Exception(
                            __('Could not find "%1" column in product data', McmHelper::CSV_MIRAKL_PRODUCT_SKU)
                        );
                    }

                    $miraklProductId = $data[McmHelper::CSV_MIRAKL_PRODUCT_ID];
                    $miraklProductSku = $data[McmHelper::CSV_MIRAKL_PRODUCT_SKU];

                    if (empty($miraklProductId)) {
                        throw new \Exception(__('Column "%1" cannot be empty', McmHelper::CSV_MIRAKL_PRODUCT_ID));
                    }

                    $this->validateIdentifier($data);

                    $product = $this->adapter->import($data);

                    $time = round(microtime(true) - $start, 2);
                    $message = __('[OK] Product has been saved for line %1 (%2s)', $i, $time);
                    $process->output($message);

                    // Register report not only on product creation
                    $this->writeSuccessReport($miraklProductId, $product->getSku());

                    if ($i % 5 === 0) {
                        $processResource->save($process);
                    }
                } catch (WarningException $e) {
                    $message = __('Warning on line %1: %2', $i, $e->getMessage());
                    $process->output($message);
                    if (isset($miraklProductId) && isset($miraklProductSku)) {
                        $this->writeErrorReport($miraklProductId, $miraklProductSku, $message);
                    }
                } catch (\Exception $e) {
                    $error = __('Error on line %1: %2', $i, $e->getMessage());
                    $process->output($error);
                    if (isset($miraklProductId) && isset($miraklProductSku)) {
                        $this->writeErrorReport($miraklProductId, $miraklProductSku, $error);
                    }
                }
            }
        } catch (\Exception $e) {
            $process->fail($e->getMessage());
            throw $e;
        } finally {
            if ($sendReport) {
                $this->apiConfigHelper->enable();
                $process->output(__('Sending integration report to Mirakl...'));
                $this->sendIntegrationReport();
            }

            // Reindex
            $process->output(__('Reindexing...'), true);
            $this->indexer->reindex();
        }

        $process->output(__('Done!'));

        return $this;
    }

    /**
     * Sends integration report to Mirakl
     *
     * @return  string|false
     */
    private function sendIntegrationReport()
    {
        $data = array_merge($this->successReport->getContents(), $this->errorReport->getContents());

        return $this->productApiHelper->export($data);
    }

    /**
     * Validate presence and value of the identifier
     *
     * @param   array   $data
     * @return  string
     * @throws  \Exception
     */
    protected function validateIdentifier($data)
    {
        if (!isset($data[$this->identifierCode])) {
            throw new \Exception(__('Could not find "%1" column in product data', $this->identifierCode));
        }

        $identifier = trim($data[$this->identifierCode]);

        if (empty($identifier)) {
            throw new \Exception(__('Column "%1" cannot be empty', $this->identifierCode));
        }

        return $identifier;
    }

    /**
     * @param   string  $miraklProductId
     * @param   string  $productSku
     */
    private function writeSuccessReport($miraklProductId, $productSku)
    {
        $this->successReport->write([
            'mirakl_product_id' => $miraklProductId,
            'product_sku'       => $productSku,
            'acceptance'        => ['status' => ProductAcceptance::STATUS_ACCEPTED],
        ]);
    }

    /**
     * @param   string  $miraklProductId
     * @param   string  $productSku
     * @param   string  $message
     */
    private function writeErrorReport($miraklProductId, $productSku, $message)
    {
        $errorReport = [
            'mirakl_product_id'  => $miraklProductId,
            'integration_errors' => [['message' => $message]]
        ];

        if (!empty($productSku)) {
            $errorReport['product_sku'] = $productSku;
        }

        $this->errorReport->write($errorReport);
    }
}
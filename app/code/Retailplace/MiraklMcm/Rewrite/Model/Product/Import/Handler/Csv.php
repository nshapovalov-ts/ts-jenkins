<?php

/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Rewrite\Model\Product\Import\Handler;

use Magento\Framework\Exception\LocalizedException;
use Mirakl\Api\Helper\Config as ApiConfigHelper;
use Mirakl\Api\Helper\Mcm\Product as ProductApiHelper;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Mci\Helper\Hash as HashHelper;
use Mirakl\Mci\Model\Product\Import\Report\ReportInterface;
use Mirakl\Mcm\Helper\Config as McmConfig;
use Mirakl\Mcm\Helper\Data as McmHelper;
use Mirakl\Mcm\Model\Product\Import\Adapter\AdapterInterface;
use Mirakl\Mcm\Model\Product\Import\Indexer\Indexer;
use Mirakl\Process\Helper\Data as ProcessHelper;
use Mirakl\Process\Model\Process;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mirakl\Mcm\Model\Product\Import\Handler\Csv as HandlerCsv;
use Mirakl\Process\Model\ProcessFactory;
use Retailplace\MiraklMcm\Model\ProductImport;
use Retailplace\MiraklMcm\Api\ProductImportRepositoryInterface as ProductImportRepository;
use Exception;
use DateTime;
use Retailplace\MiraklMcm\Model\Queue\Publisher\Product as ProductPublisher;
use Retailplace\MiraklMcm\Api\Data\ProductImportMessageInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\Mci\Model\Product\Import\Exception\WarningException;
use Magento\Framework\Exception\CouldNotSaveException;
use Retailplace\MiraklMcm\Logger\Logger;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\DB\Adapter\DeadlockException;
use DateInterval;
use DateTimeInterface;
use Retailplace\MiraklMcm\Model\Import as Import;
use Mirakl\MCM\Front\Request\Catalog\Product\ProductExportCsvRequest;

/**
 * Class Csv
 * @see \Mirakl\Mcm\Model\Product\Import\Handler\Csv
 */
class Csv extends HandlerCsv
{
    /**
     * @var int
     */
    const RETRY_COUNT = 3;

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
     * @var ProductImportRepository
     */
    private $productImportRepository;

    /**
     * @var ProductPublisher
     */
    private $publisher;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var Import
     */
    private $import;

    /**
     * @param CoreHelper $coreHelper
     * @param ApiConfigHelper $apiConfigHelper
     * @param ProcessHelper $processHelper
     * @param ProcessResourceFactory $processResourceFactory
     * @param HashHelper $hashHelper
     * @param AdapterInterface $adapter
     * @param ReportInterface $successReport
     * @param ReportInterface $errorReport
     * @param ConnectorConfig $connectorConfig
     * @param McmConfig $config
     * @param ProductApiHelper $productApiHelper
     * @param Indexer $indexer
     * @param string $identifierCode
     * @param ProductImportRepository $productImportRepository
     * @param ProcessFactory $processFactory
     * @param ProductPublisher $publisher
     * @param Logger $logger
     * @param Import $import
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
        $identifierCode,
        ProductImportRepository $productImportRepository,
        ProcessFactory $processFactory,
        ProductPublisher $publisher,
        Logger $logger,
        Import $import
    ) {
        parent::__construct(
            $coreHelper,
            $apiConfigHelper,
            $processHelper,
            $processResourceFactory,
            $hashHelper,
            $adapter,
            $successReport,
            $errorReport,
            $connectorConfig,
            $config,
            $productApiHelper,
            $indexer,
            $identifierCode
        );
        $this->productImportRepository = $productImportRepository;
        $this->processFactory = $processFactory;
        $this->publisher = $publisher;
        $this->logger = $logger;
        $this->import = $import;
    }

    /**
     * @param Process $process
     * @param DateTime|null $since
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
            $since->sub(new DateInterval('P0DT0H0M5S'));
            $process->output(__(
                'Downloading MCM products from Mirakl to Magento since %1',
                $since->format('Y-m-d H:i:s')
            ), true);
            $importParams = ['updated_since' => $since->format(DateTimeInterface::ATOM)];
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
     * Re-Import Faulty Products
     *
     * @param array|null $skus
     */
    public function reImportFaultyProducts(?array $skus = null)
    {
        try {
            $variantGroupCodes = [];
            if (empty($skus)) {
                $products = $this->import->getFaultyProducts();
                if (!empty($products)) {
                    foreach ($products as $item) {
                        $variantGroupCodes[] = $item['variant_group_code'];
                    }
                }
            }

            if (empty($skus) && empty($variantGroupCodes)) {
                $this->logger->info(__('No defective products were found, there is nothing to import.'));
                return;
            }

            if (!empty($skus)) {
                $dataArray = $skus;
            } else {
                $dataArray = $variantGroupCodes;
            }

            $aggregatedArray = [];

            $i = 0;
            foreach ($dataArray as $value) {
                if (empty($aggregatedArray[$i])) {
                    $aggregatedArray[$i][] = $value;
                    continue;
                }

                $concatArray = array_merge($aggregatedArray[$i], [$value]);
                if (strlen(implode('%2C', $concatArray)) > 100) {
                    $i++;
                }
                $aggregatedArray[$i][] = $value;
            }

            foreach ($aggregatedArray as $data) {
                try {
                    $request = new ProductExportCsvRequest();
                    if (!empty($skus)) {
                        $request->setProductSku($data);
                    } else {
                        $request->setVariantGroupCode($data);
                    }

                    $this->productApiHelper->getClient()->disable(false);

                    $apiFile = $this->productApiHelper
                        ->send($request)
                        ->getFile();

                    $process = $this->processFactory->create();

                    if ($apiFile->fstat()['size'] > 0 && ($file = $this->processHelper->saveFile($apiFile))) {
                        $fileSize = $this->coreHelper->formatSize(filesize($file));
                        $process->output(__('File has been saved as "%1" (%2)', basename($file), $fileSize));
                        $process->setFile($file);
                        $this->run($process, null);
                    } else {
                        $this->logger->error(
                            __('Didn\'t receive data for products for (%1)', implode(',', $data))
                        );
                    }
                } catch (Exception $e) {
                    $this->logger->error(
                        __(
                            'Error reimport product for (%1), %2',
                            implode(',', $data),
                            $e->getMessage()
                        )
                    );
                }
            }
        } catch (Exception $e) {
            $this->logger->error(
                __(
                    'Error reimport product for (%1), %2',
                    implode(',', !empty($skus) ? $skus : $variantGroupCodes),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @param Process $process
     * @param DateTime|null $since
     * @param bool $sendReport
     * @return  $this
     * @throws  Exception
     */
    public function run(Process $process, $since, $sendReport = true): Csv
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

            // Disable or not the indexing when UpdateOnSave mode
            $this->indexer->initIndexers();

            $process->output(__('Importing MCM file...'), true);

            $i = 0; // Line number
            $cols = []; // Used to map keys and values

            $this->delimiter = $this->getValidDelimiter($fh, $this->delimiter, $this->enclosure);

            if (false === $this->delimiter) {
                $process->output(__('No valid delimiter found. Import canceled.'));

                return $this;
            }

            $updateData = [];

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
                        throw new Exception(
                            __('Could not find "%1" column in product data', McmHelper::CSV_MIRAKL_PRODUCT_ID)
                        );
                    }

                    if (!isset($data[McmHelper::CSV_MIRAKL_PRODUCT_SKU])) {
                        throw new Exception(
                            __('Could not find "%1" column in product data', McmHelper::CSV_MIRAKL_PRODUCT_SKU)
                        );
                    }

                    $miraklProductId = !empty($data[McmHelper::CSV_MIRAKL_PRODUCT_ID]) ?
                        $data[McmHelper::CSV_MIRAKL_PRODUCT_ID] : null;
                    $miraklProductSku = !empty($data[McmHelper::CSV_MIRAKL_PRODUCT_SKU]) ?
                        $data[McmHelper::CSV_MIRAKL_PRODUCT_SKU] : null;
                    $miraklCreationDate = !empty($data['mirakl-creation-date']) ? $data['mirakl-creation-date'] : null;
                    $miraklUpdatedDate = !empty($data['mirakl-update-date']) ? $data['mirakl-update-date'] : null;

                    if (empty($miraklProductId)) {
                        throw new Exception(__('Column "%1" cannot be empty', McmHelper::CSV_MIRAKL_PRODUCT_ID));
                    }

                    $this->validateIdentifier($data);

                    $updateData[$miraklProductId] = [
                        'mirakl_product_id' => $miraklProductId,
                        'sku'               => $miraklProductSku,
                        'created_at'        => date('Y-m-d H:i:s'),
                        'mirakl_created_at' => $miraklCreationDate,
                        'mirakl_updated_at' => $miraklUpdatedDate,
                        'data'              => json_encode($data),
                        'status'            => ProductImport::STATUS_PENDING,
                        'send_status'       => ProductImport::SEND_STATUS_NOT_SENT,
                        'error'             => ""
                    ];

                    $time = round(microtime(true) - $start, 2);
                    $message = __('[OK] Product has been set to Queue for line %1 (%2s)', $i, $time);
                    $process->output($message);

                    if ($i % 5 === 0) {
                        $processResource->save($process);
                    }
                } catch (Exception $e) {
                    $error = __('Error on line %1: %2', $i, $e->getMessage());
                    $process->output($error);
                }
            }

            if (!empty($updateData)) {
                $this->productImportRepository->updateProduct($updateData);

                foreach ($updateData as $id => $updateDatum) {
                    //set mirakl product id to queue
                    $message = $this->publisher->createMessage(['id' => $id, 'retry_count' => 0]);
                    $this->publisher->execute($message);
                }
            }
        } catch (Exception $e) {
            $process->fail($e->getMessage());
            throw $e;
        }

        $process->output(__('Done!'));

        return $this;
    }

    /**
     * Validate presence and value of the identifier
     *
     * @param array $data
     * @return  string
     * @throws  Exception
     */
    protected function validateIdentifier($data): string
    {
        if (!isset($data[$this->identifierCode])) {
            throw new Exception(__('Could not find "%1" column in product data', $this->identifierCode));
        }

        $identifier = trim($data[$this->identifierCode]);

        if (empty($identifier)) {
            throw new Exception(__('Column "%1" cannot be empty', $this->identifierCode));
        }

        return $identifier;
    }

    /**
     * Import Product
     *
     * @param ProductImportMessageInterface $message
     */
    public function importProduct(ProductImportMessageInterface $message)
    {
        $id = $message->getId();

        try {
            $model = $this->productImportRepository->getByProductId($id);

            $status = $model->getStatus();
            $productData = $model->getProductData();

            if (!in_array($status, [ProductImport::STATUS_PENDING, ProductImport::STATUS_IN_PROGRESS])) {
                return;
            }

            if (empty($productData)) {
                throw new Exception("product data is empty");
            }

            $model->setStatus(ProductImport::STATUS_IN_PROGRESS);
            $this->productImportRepository->save($model);

            $product = $this->adapter->import(json_decode($productData, true));

            //reload model
            $model = $this->productImportRepository->getByProductId($id);

            $currentStatus = $model->getStatus();
            if ($currentStatus === ProductImport::STATUS_PENDING) {
                return;
            }

            $model->setSku($product->getSku());
            $model->setStatus(ProductImport::STATUS_SUCCESS);
            $this->productImportRepository->save($model);
        } catch (Exception | WarningException | NoSuchEntityException $e) {
            $errorMessage = $e->getMessage();
            $isNeedRepeat = false;

            //detect error type
            if ($e instanceof ConnectionException ||
                $e instanceof LockWaitException ||
                $e instanceof DeadlockException) {
                $isNeedRepeat = true;
            }

            if (!empty($model)) {
                $errorType = $e instanceof WarningException ? 'Warning' : 'Error';
                $this->setError($model, __('%1 on id %2: %3', $errorType, $id, $errorMessage)->render());

                $retryCount = $message->getRetryCount();

                if ($isNeedRepeat && $retryCount < self::RETRY_COUNT) { //retry if did detect mysql timeout
                    $retryCount++;
                    $message->setRetryCount($retryCount);
                    //send to Queue
                    $this->publisher->execute($message);
                } else {
                    $model->setStatus(ProductImport::STATUS_ERROR);
                }

                $this->saveProductImport($model);
            } else {
                $this->logger->error(__('Model not loaded for ID %s, %s', $id, $errorMessage));
            }
        }
    }

    /**
     * Set Error
     *
     * @param ProductImport $model
     * @param string $error
     */
    private function setError(ProductImport $model, string $error)
    {
        $oldError = $model->getError();
        if (!empty($oldError)) {
            $error = $oldError . ', ' . __('%1 - %2', date('Y-m-d H:i:s'), $error)->render();
        }

        $model->setError($error);
    }

    /**
     * Save Product Import
     *
     * @param ProductImport $model
     */
    private function saveProductImport(ProductImport $model)
    {
        try {
            $this->productImportRepository->save($model);
        } catch (CouldNotSaveException $e) {
            $data = $model->getData();
            $this->logger->warning(__(
                'Data save error: data (%s), error_message(%s)',
                json_encode($data),
                $e->getMessage()
            ));
        }
    }

    /**
     * Sends integration report to Mirakl
     *
     * @return  void
     */
    public function sendReport()
    {
        $ids = [];
        $isError = false;
        try {
            $data = $this->productImportRepository->getProductsObjectForSendReportToMirakl();
            if (!empty($data)) {
                $this->productApiHelper->export($data);
                $this->logger->info(__('Sending integration report to Mirakl...'));

                foreach ($data as $datum) {
                    $ids[] = $datum['mirakl_product_id'];
                }
            }
        } catch (Exception $e) {
            $isError = true;
            $this->logger->error(__('Error sending integration report to Mirakl..., %s', $e->getMessage()));
        }

        if (!$isError && !empty($ids)) {
            try {
                $this->productImportRepository->updateProductStatus($ids);
            } catch (Exception $e) {
                $this->logger->error(
                    __('Error update product status for ids (%s), %s', implode(',', $ids), $e->getMessage())
                );
            }
        }
    }

    /**
     * Resend Failed Products To Queue
     */
    public function resendFailedProducts()
    {
        try {
            $data = $this->productImportRepository->getProductsObjectForResendToQueue();
            if (!empty($data)) {
                $this->logger->info(__('Resending failed products to queue...'));

                foreach ($data as $datum) {
                    try {
                        $message = $this->publisher->createMessage(
                            ['id' => $datum['mirakl_product_id'], 'retry_count' => 1]
                        );
                        $this->publisher->execute($message);
                    } catch (Exception $e) {
                        $this->logger->error(__('Error resending failed products to queue..., %s', $e->getMessage()));
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->error(__('Error resending failed products to queue..., %s', $e->getMessage()));
        }
    }
}

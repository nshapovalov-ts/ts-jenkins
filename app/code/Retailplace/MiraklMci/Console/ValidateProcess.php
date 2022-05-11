<?php

namespace Retailplace\MiraklMci\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Exception\LocalizedException;
use Mirakl\Api\Helper\Config as ApiConfigHelper;
use Mirakl\Api\Helper\Mcm\Product as ProductApiHelper;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Mci\Helper\Hash as HashHelper;
use Mirakl\Mci\Model\Product\Import\Exception\WarningException;
use Mirakl\Mci\Model\Product\Import\Handler\CsvTrait;
use Mirakl\Mcm\Helper\Config as McmConfig;
use Mirakl\Mcm\Helper\Data as McmHelper;
use Mirakl\Mcm\Model\Product\Import\Adapter\AdapterInterface;
use Mirakl\Mcm\Model\Product\Import\Indexer\Indexer;
use Mirakl\Process\Helper\Data as ProcessHelper;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mirakl\Process\Model\ProcessFactory;
use Magento\Framework\App\ResourceConnection;
use Exception;
use InvalidArgumentException;
use Magento\Eav\Model\Config;
use Mirakl\MCM\Front\Domain\Product\Synchronization\ProductAcceptance;

class ValidateProcess extends Command
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
     * Run specific id key
     */
    const RUN_PROCESS_OPTION = 'run';

    /**
     * @var ProcessFactory
     */
    private $processFactory;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @param ProcessFactory $processFactory
     * @param ProcessResourceFactory $processResourceFactory
     * @param ProcessHelper $processHelper
     * @param ResourceConnection $resource
     * @param CoreHelper $coreHelper
     * @param ApiConfigHelper $apiConfigHelper
     * @param HashHelper $hashHelper
     * @param AdapterInterface $adapter
     * @param ConnectorConfig $connectorConfig
     * @param McmConfig $config
     * @param ProductApiHelper $productApiHelper
     * @param Config $eavConfig
     * @param string|null $name
     */
    public function __construct(
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        ProcessHelper $processHelper,
        ResourceConnection $resource,
        CoreHelper $coreHelper,
        ApiConfigHelper $apiConfigHelper,
        HashHelper $hashHelper,
        AdapterInterface $adapter,
        ConnectorConfig $connectorConfig,
        McmConfig $config,
        ProductApiHelper $productApiHelper,
        Config $eavConfig,
        $name = null
    ) {
        parent::__construct($name);
        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->processHelper = $processHelper;
        $this->connection = $resource->getConnection();
        $this->coreHelper = $coreHelper;
        $this->apiConfigHelper = $apiConfigHelper;
        $this->hashHelper = $hashHelper;
        $this->adapter = $adapter;
        $this->connectorConfig = $connectorConfig;
        $this->config = $config;
        $this->productApiHelper = $productApiHelper;
        $this->eavConfig = $eavConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::RUN_PROCESS_OPTION,
                null,
                InputOption::VALUE_REQUIRED,
                'Execute a specific process id'
            ),
        ];

        $this->setName('mirakl:validate_process')
            ->setDescription('check and send successful product by process')
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Start - Check and send successful product by process");

        try {
            $process = null;

            if ($processId = $input->getOption(self::RUN_PROCESS_OPTION)) {
                $output->writeln(sprintf('<info>Check Processing #%s</info>', $processId));
                $process = $this->processFactory->create();
                $this->processResourceFactory->create()->load($process, $processId);
            }

            if (empty($process) || !$process->getId()) {
                throw new InvalidArgumentException('This process no longer exists.');
            }

            $file = $process->getFile();

            if (empty($file)) {
                $output->writeln(__('No products to import'));
                return $this;
            }

            set_time_limit(0); // Script may take a while

            $fh = fopen($file, 'r');
            if (!$fh) {
                throw new LocalizedException(__('Could not read file "%1"', $file));
            }

            $output->writeln(__('Check MCM file...'), true);

            $i = 0; // Line number
            $cols = []; // Used to map keys and values

            $this->delimiter = $this->getValidDelimiter($fh, $this->delimiter, $this->enclosure);

            if (false === $this->delimiter) {
                $output->writeln(__('No valid delimiter found. Import canceled.'));
                return $this;
            }

            $allIds = [];
            // Loop through CSV file
            while ($row = fgetcsv($fh, 0, $this->delimiter, $this->enclosure)) {
                if (++$i === 1) {
                    $cols = $row; // Stores column names from first line
                    continue;
                }

                try {
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

                    $miraklProductId = $data[McmHelper::CSV_MIRAKL_PRODUCT_ID];
                    $allIds[$miraklProductId] = $miraklProductId;
                } catch (WarningException | Exception $e) {
                    $error = __('Error on line %1: %2', $i, $e->getMessage());
                    $output->writeln($error);
                }
            }
            fclose($fh);

            //check exist product from magento
            if (empty($allIds)) {
                $output->writeln('Error file');
                return $this;
            }

            $existProducsIds = [];
            $notExistIds = [];
            $sendApiData = [];

            try {
                $attributeId = $this->eavConfig->getAttribute('catalog_product', 'mirakl_mcm_product_id')->getAttributeId();
                $select = $this->connection->select()
                    ->from(['e' => 'catalog_product_entity'], ['id' => 'e.entity_id', 'sku' => 'e.sku', 'mirakl_mcm_product_id' => 'ev.value'])
                    ->joinInner(['ev' => 'catalog_product_entity_varchar'], "ev.entity_id = e.entity_id AND ev.attribute_id = " . $attributeId)
                    ->where('ev.value in (?)', $allIds);
                $resultArray = $this->connection->fetchAssoc($select);

                foreach ($resultArray as $resultData) {
                    $id = $resultData['mirakl_mcm_product_id'];
                    $productSku = $resultData['sku'];
                    $existProducsIds[$id] = $id;

                    $sendApiData[] = [
                        'mirakl_product_id' => $id,
                        'product_sku'       => $productSku,
                        'acceptance'        => ['status' => ProductAcceptance::STATUS_ACCEPTED],
                    ];
                }

                $notExistIds = array_diff_assoc($allIds, $existProducsIds);
            } catch (Exception $e) {
                $error = __('Error on line %1: %2', $i, $e->getMessage());
                $output->writeln($error);
            }

            if (empty($notExistIds)) {
                $output->writeln('Process is valid');
                return $this;
            }

            //send data to mirakl api
            if (!empty($sendApiData)) {
                $this->productApiHelper->export($sendApiData);
                $output->writeln(__('Sending integration report to Mirakl...'));
            }

            //create new csv file
            $newFileName = 'var/mirakl/validate_file_' . date('Y_m_d_H_i_s') . '.csv';
            $fpNewFile = fopen($newFileName, 'w');
            $fh = fopen($file, 'r');
            $i = 0;

            while ($row = fgetcsv($fh, 0, $this->delimiter, $this->enclosure)) {
                if (++$i === 1) {
                    $cols = $row; // Stores column names from first line
                    fputcsv($fpNewFile, $row);
                    continue;
                }

                try {
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

                    $miraklProductId = $data[McmHelper::CSV_MIRAKL_PRODUCT_ID];
                    if (array_key_exists($miraklProductId, $notExistIds)) {
                        fputcsv($fpNewFile, $row);
                    }
                } catch (WarningException | Exception $e) {
                    $error = __('Error on line %1: %2', $i, $e->getMessage());
                    $output->writeln($error);
                }
            }
            fclose($fh);
            fclose($fpNewFile);

            $output->writeln('Save new file with the not exists products (' . $newFileName . ')');
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }

        $output->writeln("End");
        return $this;
    }
}

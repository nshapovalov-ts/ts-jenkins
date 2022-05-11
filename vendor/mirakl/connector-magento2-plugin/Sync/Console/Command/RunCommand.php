<?php
namespace Mirakl\Sync\Console\Command;

use Magento\Framework\Module\Manager as ModuleManager;
use Mirakl\Catalog\Helper\Config as CatalogConfigHelper;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Mci\Helper\Config as MciConfigHelper;
use Mirakl\Mcm\Helper\Config as McmConfigHelper;
use Mirakl\Process\Model\Process;
use Mirakl\Process\Model\ProcessFactory;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mirakl\Sync\Model\Sync\Script;
use Mirakl\Sync\Model\Sync\ScriptFactory;
use Mirakl\Sync\Model\Sync\Script\CollectionFactory as ScriptCollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    const LIST_SCRIPTS_OPTION = 'list';
    const RUN_SCRIPT_OPTION   = 'run';

    /**
     * @var ScriptFactory
     */
    protected $scriptFactory;

    /**
     * @var ScriptCollectionFactory
     */
    protected $scriptCollectionFactory;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @var array
     */
    public static $scripts = [
        'Mirakl_Connector' => [
            [
                'code'   => 'S20',
                'title'  => 'Import Mirakl shops into Magento',
                'helper' => 'Mirakl\Connector\Helper\Shop',
                'method' => 'synchronize',
            ],
            [
                'code'   => 'OF51',
                'title'  => 'Import Mirakl offers into Magento',
                'helper' => 'Mirakl\Connector\Helper\Offer\Import',
                'method' => 'run',
            ],
        ],
        'Mirakl_Catalog' => [
            [
                'code'   => 'CA01',
                'title'  => 'Export enabled marketplace categories to Mirakl',
                'helper' => 'Mirakl\Catalog\Helper\Category',
                'method' => 'exportAll',
                'config' => CatalogConfigHelper::XML_PATH_ENABLE_SYNC_CATEGORIES,
            ],
            [
                'code'   => 'P21',
                'title'  => 'Export enabled products to Mirakl',
                'helper' => 'Mirakl\Catalog\Helper\Product',
                'method' => 'exportAll',
                'config' => CatalogConfigHelper::XML_PATH_ENABLE_SYNC_PRODUCTS,
            ],
        ],
        'Mirakl_Mci' => [
            [
                'code'   => 'VL01',
                'title'  => 'Export all attribute value lists to Mirakl',
                'helper' => 'Mirakl\Mci\Helper\ValueList',
                'method' => 'exportAttributes',
                'config' => MciConfigHelper::XML_PATH_ENABLE_SYNC_VALUES_LISTS,
            ],
            [
                'code'   => 'H01',
                'title'  => 'Export all Catalog categories to Mirakl',
                'helper' => 'Mirakl\Mci\Helper\Hierarchy',
                'method' => 'exportAll',
                'config' => MciConfigHelper::XML_PATH_ENABLE_SYNC_HIERARCHIES,
            ],
            [
                'code'   => 'PM01',
                'title'  => 'Export all attributes to Mirakl',
                'helper' => 'Mirakl\Mci\Helper\Attribute',
                'method' => 'exportAll',
                'config' => MciConfigHelper::XML_PATH_ENABLE_SYNC_ATTRIBUTES,
            ],
        ],
        'Mirakl_Mcm' => [
            [
                'code'   => 'CM21',
                'title'  => 'Export all operator products to Mirakl',
                'helper' => 'Mirakl\Mcm\Helper\Product\Export\Process',
                'method' => 'exportAll',
                'config' => McmConfigHelper::XML_PATH_ENABLE_SYNC_MCM_PRODUCTS,
            ],
        ],
    ];

    /**
     * @param   ScriptFactory           $scriptFactory
     * @param   ScriptCollectionFactory $scriptCollectionFactory
     * @param   ProcessFactory          $processFactory
     * @param   ProcessResourceFactory  $processResourceFactory
     * @param   ModuleManager           $moduleManager
     * @param   ConnectorConfig         $connectorConfig
     * @param   null                    $name
     */
    public function __construct(
        ScriptFactory $scriptFactory,
        ScriptCollectionFactory $scriptCollectionFactory,
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        ModuleManager $moduleManager,
        ConnectorConfig $connectorConfig,
        $name = null
    ) {
        parent::__construct($name);
        $this->scriptFactory = $scriptFactory;
        $this->scriptCollectionFactory = $scriptCollectionFactory;
        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->moduleManager = $moduleManager;
        $this->connectorConfig = $connectorConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::LIST_SCRIPTS_OPTION,
                null,
                InputOption::VALUE_NONE,
                'List synchronization scripts'
            ),
            new InputOption(
                self::RUN_SCRIPT_OPTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Execute a synchronization script by its code'
            ),
        ];

        $this->setName('mirakl:sync')
            ->setDescription('Handles synchronization scripts between Magento and the Mirakl platform')
            ->setDefinition($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('list')) {
            /** @var Script $script */
            foreach ($this->getScripts() as $script) {
                $output->writeln(sprintf('<info>%-6s</info> %s', $script->getCode(), $script->getTitle()));
            }
        } elseif ($code = $input->getOption('run')) {
            $script = $this->getScripts()->getItemById($code);

            if (!$script) {
                throw new \InvalidArgumentException('Invalid script code specified.');
            }

            if ($script->isSyncDisable()) {
                $output->writeln(sprintf('Synchronization is disabled for %s', $script->getCode()));

                return;
            }

            /** @var Process $process */
            $process = $this->processFactory->create();
            $process->setStatus(Process::STATUS_PENDING)
                ->setType(Process::TYPE_CLI)
                ->setName($script->getCode() . ' synchronization script')
                ->setHelper($script->getHelper())
                ->setMethod($script->getMethod())
                ->setParams($this->getParams($script->getCode()));

            $this->processResourceFactory->create()->save($process);

            $process->addOutput('cli');
            $process->run();
        }
    }

    /**
     * @param   string  $code
     * @return  array
     */
    public function getParams($code)
    {
        switch ($code) {
            case 'S20':
                $since = $this->connectorConfig->getSyncDate('shops');
                $this->connectorConfig->setSyncDate('shops');

                return [$since];

            default:
                return [];
        }
    }

    /**
     * @return  Script\Collection
     */
    protected function getScripts()
    {
        /** @var Script\Collection $scripts */
        $collection = $this->scriptCollectionFactory->create();

        foreach (static::$scripts as $moduleName => $scripts) {
            if ($this->moduleManager->isEnabled($moduleName)) {
                foreach ($scripts as $data) {
                    $collection->addItem($this->scriptFactory->create()->setData($data));
                }
            }
        }

        return $collection;
    }
}

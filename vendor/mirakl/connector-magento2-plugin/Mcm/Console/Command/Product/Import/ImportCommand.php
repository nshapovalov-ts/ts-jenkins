<?php
namespace Mirakl\Mcm\Console\Command\Product\Import;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Core\Console\Command\CommandTrait;
use Mirakl\Mcm\Helper\Config;
use Mirakl\Mcm\Helper\Product\Import\Process as ImportProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    use CommandTrait;

    const UPDATED_SINCE_OPTION = 'since';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ConfigInterface
     */
    private $configManager;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var ConnectorConfig
     */
    private $connectorConfig;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ImportProcess
     */
    private $importProcess;

    /**
     * @param   ObjectManagerInterface  $objectManager
     * @param   ConfigInterface         $configManager
     * @param   State                   $state
     * @param   ConnectorConfig         $connectorConfig
     * @param   Config                  $config
     * @param   ImportProcess           $importProcess
     * @param   string|null             $name
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ConfigInterface $configManager,
        State $state,
        ConnectorConfig $connectorConfig,
        Config $config,
        ImportProcess $importProcess,
        $name = null
    ) {
        parent::__construct($name);
        $this->objectManager   = $objectManager;
        $this->configManager   = $configManager;
        $this->appState        = $state;
        $this->connectorConfig = $connectorConfig;
        $this->config          = $config;
        $this->importProcess   = $importProcess;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::UPDATED_SINCE_OPTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'MCM: Export starting date. Given date must respect ISO-8601 format and must be URL encoded',
                null
            ),
        ];

        $this->setName('mirakl:mcm:product:import')
            ->setDescription('Handles Mirakl MCM product import')
            ->setDefinition($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initAuthorization();

        $this->setAreaCode(Area::AREA_ADMINHTML);

        $isEnabled = $this->config->isMcmEnabled();

        if ($isEnabled) {
            $output->writeln('Importing MCM ...');
            $updatedSince = $input->getOption(self::UPDATED_SINCE_OPTION);

            if (empty($updatedSince)) {
                $updatedSince = $this->connectorConfig->getSyncDate('mcm_products_import');
            } else {
                $updatedSince = new \DateTime($updatedSince);
            }

            $this->importProcess->runApi($updatedSince);
        } else {
            $output->writeln('Mirakl MCM is not activated in your configuration');
        }
    }
}

<?php
namespace Mirakl\Mci\Console\Command\Product\Import;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Mirakl\Core\Console\Command\CommandTrait;
use Mirakl\Mci\Helper\Config;
use Mirakl\Mci\Helper\Product\Import as Handler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PathCommand extends Command
{
    use CommandTrait;

    const PROCESS_TYPE = 'CLI';

    const MAX_FILES_OPTION = 'max-files';

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
     * @var Config
     */
    private $config;

    /**
     * @var Handler
     */
    private $handler;

    /**
     * @param   ObjectManagerInterface  $objectManager
     * @param   ConfigInterface         $configManager
     * @param   State                   $state
     * @param   Config                  $config
     * @param   Handler                 $handler
     * @param   string|null             $name
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ConfigInterface $configManager,
        State $state,
        Config $config,
        Handler $handler,
        $name = null
    ) {
        parent::__construct($name);
        $this->objectManager = $objectManager;
        $this->configManager = $configManager;
        $this->appState = $state;
        $this->config = $config;
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::MAX_FILES_OPTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum number of files to process in a single execution',
                Handler::IMPORT_PATH_MAX_FILES
            ),
        ];

        $this->setName('mirakl:mci:product-import-path')
            ->setDescription('Handles MCI product import from path')
            ->setDefinition($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initAuthorization();

        $this->setAreaCode(Area::AREA_ADMINHTML);
        $maxFiles = (int) $input->getOption('max-files');
        $path = $this->config->getImportPath();
        $this->handler->runPath($path, $maxFiles);
    }
}

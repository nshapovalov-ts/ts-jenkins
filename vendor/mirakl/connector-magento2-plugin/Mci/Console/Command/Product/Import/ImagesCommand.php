<?php
namespace Mirakl\Mci\Console\Command\Product\Import;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Mirakl\Core\Console\Command\CommandTrait;
use Mirakl\Mci\Helper\Config;
use Mirakl\Process\Console\Command\ParallelCommandTrait;
use Mirakl\Process\Helper\Data as ProcessHelper;
use Mirakl\Process\Model\Process;
use Mirakl\Process\Model\ProcessFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImagesCommand extends Command
{
    use CommandTrait;
    use ParallelCommandTrait;

    const PROCESS_NAME = 'Products images import';

    const LIMIT_OPTION = 'limit';

    /**
     * @var State
     */
    private $appState;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var ProcessHelper
     */
    private $helper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param   State           $state
     * @param   ProcessFactory  $processFactory
     * @param   ProcessHelper   $helper
     * @param   Config          $config
     * @param   string|null     $name
     */
    public function __construct(
        State $state,
        ProcessFactory $processFactory,
        ProcessHelper $helper,
        Config $config,
        $name = null
    ) {
        parent::__construct($name);
        $this->appState = $state;
        $this->processFactory = $processFactory;
        $this->helper = $helper;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::LIMIT_OPTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum number of images to import'
            ),
        ];
        $options = $this->addCheckRunningOption($options);

        $this->setName('mirakl:mci:product-import-images')
            ->setDescription('Handles MCI product images import')
            ->setDefinition($options);
    }

    /**
     * Creates a Mirakl process
     *
     * @param   InputInterface  $input
     * @return  Process
     */
    private function createProcess(InputInterface $input)
    {
        $process = $this->processFactory->create();
        $process->setType(Process::TYPE_CLI)
            ->setName(self::PROCESS_NAME)
            ->setStatus(Process::STATUS_PENDING)
            ->setHelper(\Mirakl\Mci\Helper\Product\Image::class)
            ->setMethod('run');

        $limit = $input->getOption('limit');

        if (!$limit) {
            $limit = $this->config->getImagesImportLimit();
        }

        $process->setParams([(int) $limit]);

        return $process;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setAreaCode(Area::AREA_ADMINHTML);

        if ($this->checkRunningProcess($input, $output, \Mirakl\Mci\Helper\Product\Image::class, 'run')) {
            return;
        }

        $this->createProcess($input)->run();
    }
}

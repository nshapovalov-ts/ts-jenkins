<?php
namespace Mirakl\Process\Console\Command;

use Mirakl\Process\Helper\Data as Helper;
use Mirakl\Process\Model\Process;
use Mirakl\Process\Model\ProcessFactory;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ApiReturnProcessCommand extends Command
{
    /**
     * Run specific id key
     */
    const RUN_PROCESS_OPTION = 'run';

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param   ProcessFactory          $processFactory
     * @param   ProcessResourceFactory  $processResourceFactory
     * @param   Helper                  $helper
     * @param   string|null             $name
     */
    public function __construct(
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        Helper $helper,
        $name = null
    ) {
        parent::__construct($name);
        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->helper = $helper;
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

        $this->setName('mirakl:process:api')
            ->setDescription('Handles Mirakl Api return processes execution')
            ->setDefinition($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($processId = $input->getOption(self::RUN_PROCESS_OPTION)) {
            $process = $this->processFactory->create();
            $this->processResourceFactory->create()->load($process, $processId);
            if (!$process->getId()) {
                throw new \InvalidArgumentException('This process no longer exists.');
            }
            if (!$process->canCheckMiraklStatus()) {
                throw new \Exception('Mirakl status cannot be checked on this process.');
            }
            $process->addOutput('cli');
            $process->checkMiraklStatus();
        } else {
            $processes = $this->helper->getMiraklStatusToCheckProcesses();
            if ($processes->count() > 0) {
                foreach ($processes as $process) {
                    /** @var Process $process */
                    $output->writeln(sprintf('<info>Processing API Status #%s %s</info>', $process->getId(), $process->getName()));
                    $process->addOutput('cli');
                    $process->checkMiraklStatus();
                }
            } else {
                $output->writeln('<error>Nothing to be processed</error>');
            }
        }
    }
}

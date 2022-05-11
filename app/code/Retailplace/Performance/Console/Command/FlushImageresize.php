<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\Performance\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\ObjectManagerInterface;
use Retailplace\Performance\Helper\Data;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
class FlushImageresize extends Command
{

    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";

    /**
     * @var State
     */
    private $appState;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    protected $logger;

    protected $helper;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param State $state
     * @param \Psr\Log\LoggerInterface $logger
     * @param Data $helper
     * @param string|null $name
     */
    public function __construct(
        State $state,
        \Psr\Log\LoggerInterface $logger,
        Data $helper,
        ObjectManagerInterface $objectManager,
        $name = null
    ) {
        parent::__construct($name);
        $this->helper = $helper;
        $this->logger = $logger;
        $this->appState = $state;
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("retailplace_performance:all-imageresize");
        $this->setDescription("Resize all images By Cron");

    }

    
    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        try {
            $this->appState->setAreaCode(Area::AREA_GLOBAL);
            $generator = $this->helper->resizeFromThemes();

            /** @var ProgressBar $progress */
            $progress = $this->objectManager->create(ProgressBar::class, [
                'output' => $output,
                'max' => $generator->current()
            ]);
            $progress->setFormat(
                "%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s% \t| <info>%message%</info>"
            );

            if ($output->getVerbosity() !== OutputInterface::VERBOSITY_NORMAL) {
                $progress->setOverwrite(false);
            }

            for (; $generator->valid(); $generator->next()) {
                $progress->setMessage($generator->key());
                $progress->advance();
            }
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $output->write(PHP_EOL);
        $output->writeln("<info>Product images resized successfully</info>");
    }


}


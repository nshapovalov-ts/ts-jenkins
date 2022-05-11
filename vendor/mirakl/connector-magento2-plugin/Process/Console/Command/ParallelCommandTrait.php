<?php
namespace Mirakl\Process\Console\Command;

use Mirakl\Process\Model\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \Mirakl\Process\Helper\Data $helper
 */
trait ParallelCommandTrait
{
    /**
     * @param   array   $options
     * @return  array
     */
    public function addCheckRunningOption(array $options)
    {
        $options[] = new InputOption(
            'check-running',
            null,
            InputOption::VALUE_NONE,
            'Verify if the same process is process is already running. Prevent to run the same process in parallel.'
        );

        return $options;
    }

    /**
     * @param   InputInterface  $input
     * @param   OutputInterface $output
     * @param   string          $helper
     * @param   string          $method
     * @return  bool
     * @throws  \Exception
     */
    public function checkRunningProcess(InputInterface $input, OutputInterface $output, $helper, $method)
    {
        if (!$input->getOption('check-running')) {
            return false;
        }

        $processes = $this->helper->checkProcessingProcess($helper, $method);
        $hasRunning = false;
        foreach ($processes as $process) {
            /** @var Process $process */
            if ($process->getStatus() == Process::STATUS_TIMEOUT) {
                $output->writeln(sprintf('Process with id %d has been change to timout.', $process->getId()));
            } elseif ($process->getStatus() == Process::STATUS_PROCESSING) {
                $output->writeln(sprintf('Process with id %d is still running.', $process->getId()));
                $hasRunning = true;
            }
        }

        return $hasRunning;
    }
}

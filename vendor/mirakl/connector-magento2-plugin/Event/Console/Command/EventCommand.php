<?php
namespace Mirakl\Event\Console\Command;

use Mirakl\Event\Helper\Data as EventHelper;
use Mirakl\Process\Model\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventCommand extends Command
{
    /**
     * @var EventHelper
     */
    private $eventHelper;

    /**
     * @param   EventHelper $eventHelper
     * @param   string|null $name
     */
    public function __construct(
        EventHelper $eventHelper,
        $name = null
    ) {
        parent::__construct($name);
        $this->eventHelper = $eventHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mirakl:event')
            ->setDescription('Handles execution of Mirakl events asynchronous synchronization');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln('<info>Executing asynchronous events...</info>');
            $process = $this->eventHelper->getOrCreateEventProcess(Process::TYPE_CLI);
            $process->execute();
            $output->writeln('<info>Done!</info>');
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }
    }
}

<?php
namespace Mirakl\Event\Helper\Process;

use Mirakl\Event\Model\Event;
use Mirakl\Process\Model\Process;

class CheckMiraklReport extends AbstractProcess
{
    /**
     * Call Mirakl API to retrieve execution report
     *
     * {@inheritdoc}
     */
    public function execute(Process $process, $type, $action)
    {
        $process->output(sprintf(
            'Checking Mirakl report "%s" for "%s"',
            Event::getTypeLabel($type),
            $action == Event::ACTION_UPDATE ? 'update' : 'delete'
        ));

        // Load events
        /** @var \Mirakl\Event\Model\ResourceModel\Event\Collection $collection */
        $collection = $this->eventCollectionFactory->create();
        $collection->addSentFilter()
            ->addTypeFilter($type)
            ->addActionFilter($action)
            ->setOrder('line', 'asc');

        if (!count($collection)) {
            goto proceed;
        }

        $synchroId = $collection->getFirstItem()->getImportId();

        try {
            // Check if complete
            $helper = $this->getSynchroHelper($type);
            $synchroResult = $helper->getSynchroResult($synchroId);

            if ($synchroResult->getStatus() == 'FAILED') {
                $process->output("Import #{$synchroId} has failed!");
                $this->updateEvents($type, $action, Event::STATUS_SENT, ['status' => Event::STATUS_MIRAKL_ERROR]);

                goto proceed;
            }

            if ($synchroResult->getStatus() != 'COMPLETE') {
                $process->idle();
                $process->output("Import #{$synchroId} not completed yet. Waiting for next check.", true);

                return $this;
            }

            if (!$synchroResult->getErrorReport()) {
                $process->output("Import #{$synchroId} is valid");
                $this->updateEvents($type, $action, Event::STATUS_SENT, ['status' => Event::STATUS_SUCCESS]);

                goto proceed;
            }

            $process->output("Parsing error report for import #{$synchroId}...");

            /** @var \SplFileObject $reportFile */
            $reportFile = $helper->getErrorReport($synchroId);
            $columns = $reportFile->fgetcsv();

            $events = $collection->getItems();
            reset($events);
            /** @var Event $event */
            $event = current($events);

            while (!$reportFile->eof()) {
                $data = array_combine($columns, $reportFile->fgetcsv());
                while ($event && $event->getLine() < $data['error-line']) {
                    $this->updateEvent($event, Event::STATUS_SUCCESS);
                    $event = next($events);
                }
                if ($event) {
                    $this->updateEvent($event, Event::STATUS_MIRAKL_ERROR, $data['error-message']);
                    $event = next($events);
                }
            }
            while ($event) {
                $this->updateEvent($event, Event::STATUS_SUCCESS);
                $event = next($events);
            }
        } catch (\Exception $e) {
            $this->updateEvents($type, $action, Event::STATUS_SENT, [
                'status' => Event::STATUS_INTERNAL_ERROR,
                'message' => $e->getMessage()
            ]);
            $process->output(sprintf('Check report in Mirakl failed : %s', $e->getMessage()));
        }

        proceed:
        return $this->proceed($process, $type, $action);
    }

    /**
     * @param   Event       $event
     * @param   string      $status
     * @param   string|null $message
     * @return  $this
     */
    private function updateEvent(Event $event, $status, $message = null)
    {
        $event->setStatus($status);
        if (null !== $message) {
            $event->setMessage($message);
        }
        $this->eventResourceFactory->create()->save($event);

        return $this;
    }
}
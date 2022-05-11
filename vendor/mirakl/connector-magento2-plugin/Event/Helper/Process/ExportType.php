<?php
namespace Mirakl\Event\Helper\Process;

use Mirakl\Event\Model\Event;
use Mirakl\Process\Model\Process;

class ExportType extends AbstractProcess
{
    /**
     * Export asynchronously all events in 'waiting' status for a specific API and action
     *
     * {@inheritdoc}
     */
    public function execute(Process $process, $type, $action)
    {
        if (!$this->miraklConfig->getFlag(Event::getSyncConfigPath($type))) {
            $process->output(sprintf('Synhronization disabled for "%s"', Event::getTypeLabel($type)));

            return $this->proceed($process, $type, $action);
        }

        $process->output(sprintf(
            'Exporting "%s" for "%s"',
            Event::getTypeLabel($type),
            $action == Event::ACTION_UPDATE ? 'update' : 'delete'
        ));

        $this->_eventManager->dispatch('mirakl_event_export_before', [
            'type' => $type,
            'action' => $action,
        ]);

        /** @var \Mirakl\Event\Model\ResourceModel\Event\Collection $collection */
        $collection = $this->eventCollectionFactory->create();
        $collection->addWaitingFilter()
            ->addTypeFilter($type)
            ->addActionFilter($action);

        if (!$collection->count()) {
            $process->output(' => Nothing to export');

            return $this->proceed($process, $type, $action);
        }

        // Update event status to 'processing' and read data
        $data = [];
        $line = 2;
        foreach ($collection as $event) {
            /** @var Event $event */
            $event->setStatus(Event::STATUS_PROCESSING);
            $event->setProcessId($process->getId());
            $event->setLine($line);
            $this->eventResourceFactory->create()->save($event);

            $data[] = $event->getCsvData();
            $line++;
        }

        $nextState = ['status' => Event::STATUS_SENT];
        $checkMiraklReport = true;

        try {
            // Export to Mirakl
            $helper = $this->getExportHelper($type);
            $synchroId = $helper->export($data);
            $nextState['import_id'] = $synchroId;

            $process->output(sprintf(
                ' => %d element(s) sent to Mirakl (Synchro Id: %d)',
                count($collection),
                $synchroId
            ));
        } catch (\Exception $e) {
            $nextState = [
                'status'  => Event::STATUS_INTERNAL_ERROR,
                'message' => $e->getMessage()
            ];
            $checkMiraklReport = false;

            $process->output(sprintf('Export to Mirakl failed: %s', $e->getMessage()));
        } finally {
            $this->processResourceFactory->create()->save($process);
        }

        // Update status to next step
        $this->updateEvents($type, $action, Event::STATUS_PROCESSING, $nextState);

        $this->_eventManager->dispatch('mirakl_event_export_after', [
            'type' => $type,
            'action' => $action,
        ]);

        return $this->proceed($process, $type, $action, $checkMiraklReport);
    }
}

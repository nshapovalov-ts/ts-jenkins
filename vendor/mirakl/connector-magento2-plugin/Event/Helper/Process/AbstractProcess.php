<?php
namespace Mirakl\Event\Helper\Process;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mirakl\Api\Helper\ExportDataInterface;
use Mirakl\Api\Helper\SynchroResultInterface;
use Mirakl\Core\Helper\Config as MiraklConfig;
use Mirakl\Event\Helper\ApiFactory as ApiHelperFactory;
use Mirakl\Event\Model\Event;
use Mirakl\Event\Model\EventFactory;
use Mirakl\Event\Model\ResourceModel\EventFactory as EventResourceFactory;
use Mirakl\Event\Model\ResourceModel\Event\CollectionFactory as EventCollectionFactory;
use Mirakl\Process\Model\Process;
use Mirakl\Process\Model\ProcessFactory;
use Mirakl\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;

abstract class AbstractProcess extends AbstractHelper
{
    /**
     * @var MiraklConfig
     */
    protected $miraklConfig;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    /**
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * @var EventResourceFactory
     */
    protected $eventResourceFactory;

    /**
     * @var EventCollectionFactory
     */
    protected $eventCollectionFactory;

    /**
     * @var ApiHelperFactory
     */
    protected $apiHelperFactory;

    /**
     * @param   Context                 $context
     * @param   MiraklConfig            $miraklConfig
     * @param   ProcessFactory          $processFactory
     * @param   ProcessResourceFactory  $processResourceFactory
     * @param   EventFactory            $eventFactory
     * @param   EventResourceFactory    $eventResourceFactory
     * @param   EventCollectionFactory  $eventCollectionFactory
     * @param   ApiHelperFactory        $apiHelperFactory
     */
    public function __construct(
        Context $context,
        MiraklConfig $miraklConfig,
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        EventFactory $eventFactory,
        EventResourceFactory $eventResourceFactory,
        EventCollectionFactory $eventCollectionFactory,
        ApiHelperFactory $apiHelperFactory
    ) {
        parent::__construct($context);
        $this->miraklConfig           = $miraklConfig;
        $this->processFactory         = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->eventFactory           = $eventFactory;
        $this->eventResourceFactory   = $eventResourceFactory;
        $this->eventCollectionFactory = $eventCollectionFactory;
        $this->apiHelperFactory       = $apiHelperFactory;
    }

    /**
     * @param   Process $process
     * @param   int     $type
     * @param   int     $action
     * @return  $this
     */
    abstract public function execute(Process $process, $type, $action);

    /**
     * @param   string  $type
     * @return  ExportDataInterface
     */
    protected function getExportHelper($type)
    {
        $helper = $this->apiHelperFactory->create($type);

        if (!$helper instanceof ExportDataInterface) {
            throw new \RuntimeException(
                sprintf('Helper %s does not implement ExportDataInterface', get_class($helper))
            );
        }

        return $helper;
    }

    /**
     * @param   string  $type
     * @return  SynchroResultInterface
     */
    protected function getSynchroHelper($type)
    {
        $helper = $this->apiHelperFactory->create($type);

        if (!$helper instanceof SynchroResultInterface) {
            throw new \RuntimeException(
                sprintf('Helper %s does not implement SynchroResultInterface', get_class($helper))
            );
        }

        return $helper;
    }

    /**
     * Set the next step to do in the process
     *
     * @param   Process $process
     * @param   int     $type
     * @param   int     $action
     * @param   bool    $checkMiraklReport
     * @return  $this
     */
    public function proceed(Process $process, $type, $action, $checkMiraklReport = false)
    {
        $process->idle();

        $helper = ExportType::class;

        if ($checkMiraklReport) {
            $helper = CheckMiraklReport::class;
        } elseif ($action == Event::ACTION_DELETE) {
            $action = Event::ACTION_UPDATE;
        } else {
            $types = array_keys(Event::getTypes());
            $nextTypePosition = array_search($type, $types) + 1;

            if (count($types) <= $nextTypePosition) {
                $process->stop();
                $this->processResourceFactory->create()->save($process);

                return $this; // nothing more to do, stop here
            }

            $type = $types[$nextTypePosition];
            $action = Event::ACTION_DELETE;
        }

        $this->updateProcess($process, $helper, 'execute', [$type, $action]);

        $process->execute();

        return $this;
    }

    /**
     * Update events for a specific type, action and status
     *
     * @param   int     $type
     * @param   int     $action
     * @param   string  $status
     * @param   array   $values
     * @return  int     Number of lines updated
     */
    protected function updateEvents($type, $action, $status, $values)
    {
        $resource   = $this->eventResourceFactory->create();
        $connection = $resource->getConnection();

        return $connection->update($resource->getMainTable(), $values, [
            'status = ?' => $status,
            'type = ?'   => $type,
            'action = ?' => $action,
        ]);
    }

    /**
     * @param   Process $process
     * @param   string  $helper
     * @param   string  $method
     * @param   array   $params
     * @return  $this
     */
    protected function updateProcess(Process $process, $helper, $method, array $params = [])
    {
        $process->setHelper($helper)
            ->setMethod($method)
            ->setParams($params);

        $this->processResourceFactory->create()->save($process);

        return $this;
    }
}

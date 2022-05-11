<?php
namespace Mirakl\Event\Observer\Attribute;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Event\Helper\Data as EventHelper;
use Mirakl\Event\Model\Event;
use Mirakl\Event\Model\EventFactory;
use Mirakl\Event\Model\ResourceModel\EventFactory as EventResourceFactory;
use Mirakl\Event\Model\ResourceModel\Event\CollectionFactory as EventCollectionFactory;
use Mirakl\Mci\Helper\Attribute as MciHelper;

class PrepareTreeObserver implements ObserverInterface
{
    /**
     * @var EventHelper
     */
    protected $eventHelper;

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
     * @var MciHelper
     */
    protected $mciHelper;

    /**
     * @param   EventHelper             $eventHelper
     * @param   EventFactory            $eventFactory
     * @param   EventResourceFactory    $eventResourceFactory
     * @param   EventCollectionFactory  $eventCollectionFactory
     * @param   MciHelper               $mciHelper
     */
    public function __construct(
        EventHelper $eventHelper,
        EventFactory $eventFactory,
        EventResourceFactory $eventResourceFactory,
        EventCollectionFactory $eventCollectionFactory,
        MciHelper $mciHelper
    ) {
        $this->eventHelper = $eventHelper;
        $this->eventFactory = $eventFactory;
        $this->eventResourceFactory = $eventResourceFactory;
        $this->eventCollectionFactory = $eventCollectionFactory;
        $this->mciHelper = $mciHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $type = $observer->getEvent()->getData('type');
        $action = $observer->getEvent()->getData('action');
        if ($type != Event::TYPE_PM01 || $action != Event::ACTION_DELETE) {
            return;
        }

        /** @var \Mirakl\Event\Model\ResourceModel\Event\Collection $collection */
        $collection = $this->eventCollectionFactory->create();
        $collection->addWaitingFilter()
            ->addTypeFilter(Event::TYPE_PM01)
            ->addActionFilter(Event::ACTION_PREPARE);

        if (!count($collection)) {
            return;
        }

        $attributeIds = [];
        foreach ($collection as $event) {
            /** @var Event $event */
            if ($event->getCode()) {
                $attributeIds[] = $event->getCode();
            }
            $this->eventResourceFactory->create()->delete($event);
        }

        $csvData = $this->mciHelper->prepareCsvData('update', false, $attributeIds);

        foreach ($csvData as $eventData) {
            $eventAction = $eventData['update-delete'] == 'delete'
                ? Event::ACTION_DELETE
                : Event::ACTION_UPDATE;

            // Parent code and code are concatenated to make an unique code
            $code = $eventData['hierarchy-code'] . '|' . $eventData['code'];

            $this->eventHelper->addEvent($code, $eventAction, Event::TYPE_PM01, $eventData);
        }
    }
}
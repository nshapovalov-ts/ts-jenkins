<?php
namespace Mirakl\Event\Plugin\Helper\Mci;

use Mirakl\Mci\Helper\Attribute as MciAttributeHelper;
use Mirakl\Event\Model\Event;
use Mirakl\Event\Plugin\Helper\AbstractHelperPlugin;

class AttributePlugin extends AbstractHelperPlugin
{
    /**
     * {@inheritdoc}
     */
    protected function addToEventQueue(array $attributeIds)
    {
        if (empty($attributeIds)) {
            $attributeIds = [null];
        }

        foreach ($attributeIds as $attributeId) {
            $this->eventHelper->addEvent($attributeId, Event::ACTION_PREPARE, $this->getEventType());
        }
    }

    /**
     * @param   MciAttributeHelper  $subject
     * @param   \Closure            $proceed
     * @param   string              $action
     * @param   bool                $full
     * @param   array               $attributeIds
     * @return  mixed
     */
    public function aroundExportTree(
        MciAttributeHelper $subject,
        \Closure $proceed,
        $action = 'update',
        $full = true,
        array $attributeIds = []
    ) {
        if ($full || !$this->isAsynchronousEnabled()) {
            return $proceed($action, $full, $attributeIds);
        }

        $this->addToEventQueue($attributeIds);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEventType()
    {
        return Event::TYPE_PM01;
    }
}

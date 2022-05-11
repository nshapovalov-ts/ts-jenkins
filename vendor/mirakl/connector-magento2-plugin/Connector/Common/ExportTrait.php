<?php
namespace Mirakl\Connector\Common;

use Magento\Framework\DataObject;

/**
 * @property \Magento\Framework\Event\ManagerInterface $_eventManager
 */
trait ExportTrait
{
    /**
     * @return  bool
     */
    public function isExportable()
    {
        $enabled = new DataObject(['enabled' => true]);

        $this->_eventManager->dispatch('mirakl_export_is_enabled', [
            'input'  => $enabled,
            'source' => $this->getSource(),
        ]);

        return $enabled->getData('enabled');
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return self::EXPORT_SOURCE;
    }

    /**
     * Updates given object to Mirakl platform
     *
     * @param   DataObject  $object
     * @return  int|false
     */
    public function update(DataObject $object)
    {
        return $this->export($this->wrap($object));
    }

    /**
     * Deletes given object from Mirakl platform
     *
     * @param   DataObject  $object
     * @return  int|false
     */
    public function delete(DataObject $object)
    {
        return $this->export($this->wrap($object, 'delete'));
    }

    /**
     * @param   DataObject  $object
     * @param   null|string $action
     * @return  array
     */
    protected function wrap(DataObject $object, $action = null)
    {
        return [$this->prepare($object, $action)];
    }
}

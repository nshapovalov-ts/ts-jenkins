<?php
namespace Mirakl\Connector\Common;

use Magento\Framework\DataObject;
use Mirakl\Api\Helper\ExportDataInterface;

interface ExportInterface extends ExportDataInterface
{
    /**
     * @return  bool
     */
    public function isExportable();

    /**
     * @return  string
     */
    public function getSource();

    /**
     * Prepares object data for export
     *
     * @param   DataObject  $object
     * @param   null|string $action
     * @return  array
     */
    public function prepare(DataObject $object, $action = null);
}
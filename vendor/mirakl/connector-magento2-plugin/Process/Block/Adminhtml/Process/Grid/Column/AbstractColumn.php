<?php
namespace Mirakl\Process\Block\Adminhtml\Process\Grid\Column;

use Magento\Backend\Block\Widget\Grid\Column;
use Mirakl\Process\Model\Process;

abstract class AbstractColumn extends Column
{
    /**
     * Decorates column value
     *
     * @param   string  $value
     * @param   Process $row
     * @param   Column  $column
     * @param   bool    $isExport
     * @return  string
     */
    abstract public function decorate($value, $row, $column, $isExport);

    /**
     * @return  array
     */
    public function getFrameCallback()
    {
        return [$this, 'decorate'];
    }
}
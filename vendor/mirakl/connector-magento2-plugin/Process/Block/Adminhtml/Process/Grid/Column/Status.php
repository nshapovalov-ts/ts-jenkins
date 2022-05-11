<?php
namespace Mirakl\Process\Block\Adminhtml\Process\Grid\Column;

use Mirakl\Process\Model\Process;

class Status extends AbstractColumn
{
    /**
     * {@inheritdoc}
     */
    public function decorate($value, $row, $column, $isExport)
    {
        if (!$value) return '';

        $isMirakl = strstr($column->getId(), 'mirakl') === false ? false : true;

        return '<span class="' . $row->getStatusClass($isMirakl) . '"><span>' . __($value) . '</span></span>';
    }

    /**
     * @return  array
     */
    public function getOptions()
    {
        $options = [];
        foreach (Process::getStatuses() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => __(ucwords(str_replace('_', ' ', $label))),
            ];
        }

        return $options;
    }
}

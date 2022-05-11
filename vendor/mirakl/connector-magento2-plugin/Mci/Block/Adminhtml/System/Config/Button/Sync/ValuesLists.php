<?php
namespace Mirakl\Mci\Block\Adminhtml\System\Config\Button\Sync;

use Mirakl\Connector\Block\Adminhtml\System\Config\Button\AbstractButtons;

class ValuesLists extends AbstractButtons
{
    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label'       => 'Export to Mirakl',
            'url'         => 'mirakl/sync/valuesLists',
            'confirm'     => 'Are you sure? This will export all attribute value lists to Mirakl platform.',
            'class'       => 'scalable',
            'config_path' => \Mirakl\Mci\Helper\Config::XML_PATH_ENABLE_SYNC_VALUES_LISTS,
        ],
        [
            'label'       => 'Clear in Mirakl',
            'url'         => 'mirakl/clear/valuesLists',
            'confirm'     => 'Are you sure? This will remove all attribute value lists from Mirakl platform.',
            'class'       => 'scalable primary',
            'config_path' => \Mirakl\Mci\Helper\Config::XML_PATH_ENABLE_SYNC_VALUES_LISTS,
        ],
    ];
}
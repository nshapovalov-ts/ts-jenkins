<?php
namespace Mirakl\Mci\Block\Adminhtml\System\Config\Button\Sync;

use Mirakl\Connector\Block\Adminhtml\System\Config\Button\AbstractButtons;

class Hierarchies extends AbstractButtons
{
    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label'       => 'Export to Mirakl',
            'url'         => 'mirakl/sync/hierarchies',
            'confirm'     => 'Are you sure? This will export all Catalog categories to Mirakl platform.',
            'class'       => 'scalable',
            'config_path' => \Mirakl\Mci\Helper\Config::XML_PATH_ENABLE_SYNC_HIERARCHIES,
        ],
        [
            'label'       => 'Clear in Mirakl',
            'url'         => 'mirakl/clear/hierarchies',
            'confirm'     => 'Are you sure? This will remove all Catalog categories from Mirakl platform.',
            'class'       => 'scalable primary',
            'config_path' => \Mirakl\Mci\Helper\Config::XML_PATH_ENABLE_SYNC_HIERARCHIES,
        ],
    ];
}
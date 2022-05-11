<?php
namespace Mirakl\Mci\Block\Adminhtml\System\Config\Button\Sync;

use Mirakl\Connector\Block\Adminhtml\System\Config\Button\AbstractButtons;

class Attributes extends AbstractButtons
{
    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label'       => 'Export to Mirakl',
            'url'         => 'mirakl/sync/attributes',
            'confirm'     => 'Are you sure? This will export all attributes to Mirakl platform.',
            'class'       => 'scalable',
            'config_path' => \Mirakl\Mci\Helper\Config::XML_PATH_ENABLE_SYNC_ATTRIBUTES,
        ],
        [
            'label'       => 'Clear in Mirakl',
            'url'         => 'mirakl/clear/attributes',
            'confirm'     => 'Are you sure? This will remove all attributes from Mirakl platform.',
            'class'       => 'scalable primary',
            'config_path' => \Mirakl\Mci\Helper\Config::XML_PATH_ENABLE_SYNC_ATTRIBUTES,
        ],
    ];
}
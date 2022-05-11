<?php
namespace Mirakl\Mcm\Block\Adminhtml\System\Config\Button\Sync;

use Mirakl\Connector\Block\Adminhtml\System\Config\Button\AbstractButtons;
use Mirakl\Mcm\Helper\Config;

class ExportProducts extends AbstractButtons
{
    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label'       => 'Export to Mirakl',
            'url'         => 'mirakl/sync/mcm_products',
            'confirm'     => 'Are you sure? This will export all MCM products data to Mirakl platform.',
            'class'       => 'scalable',
            'config_path' => [Config::XML_PATH_ENABLE_MCM, Config::XML_PATH_ENABLE_SYNC_MCM_PRODUCTS],
        ]
    ];
}
<?php
namespace Mirakl\Catalog\Block\Adminhtml\System\Config\Button\Sync;

use Mirakl\Connector\Block\Adminhtml\System\Config\Button\AbstractButtons;

class Categories extends AbstractButtons
{
    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label'       => 'Export to Mirakl',
            'url'         => 'mirakl/sync/categories',
            'confirm'     => 'Are you sure? This will export all enabled marketplace categories to Mirakl platform.',
            'class'       => 'scalable',
            'config_path' => \Mirakl\Catalog\Helper\Config::XML_PATH_ENABLE_SYNC_CATEGORIES,
        ]
    ];
}
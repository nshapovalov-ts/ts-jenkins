<?php
namespace Mirakl\Mci\Block\Adminhtml\System\Config\Button\Import;

use Mirakl\Connector\Block\Adminhtml\System\Config\Button\AbstractButtons;

class Images extends AbstractButtons
{
    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label' => 'Import in Magento',
            'url' => 'mirakl/sync/images',
            'confirm' => 'Are you sure? This will download and import pending products images into Magento.',
            'class' => 'scalable',
        ]
    ];
}
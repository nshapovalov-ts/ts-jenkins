<?php
namespace Mirakl\Mcm\Block\Adminhtml\System\Config\Button\Import;

use Mirakl\Connector\Block\Adminhtml\System\Config\Button\AbstractButtons;

class Products extends AbstractButtons
{
    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label'       => 'Import in Magento',
            'url'         => 'mirakl/import/products',
            'confirm'     => 'Are you sure? This will update all modified products since the last synchronization.',
            'class'       => 'scalable',
        ],
        [
            'label'   => 'Reset Date',
            'url'     => 'mirakl/reset/products',
            'confirm' => 'Are you sure? This will reset the last synchronization date.',
            'class'   => 'scalable primary',
        ],
    ];

    public function getButtonsHtml()
    {
        if (!$this->_scopeConfig->getValue(\Mirakl\Mcm\Helper\Config::XML_PATH_ENABLE_MCM)) {
            $this->setDisabled(true);
        }

        return parent::getButtonsHtml();
    }
}
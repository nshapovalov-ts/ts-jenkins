<?php
namespace Mirakl\Mci\Block\Adminhtml\System\Config\Button\Import;

use Mirakl\Connector\Block\Adminhtml\System\Config\Button\AbstractButtons;

class Products extends AbstractButtons
{
    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label' => 'Import in Magento',
            'onclick' => "var el = jQuery('#mirakl_mci_import_shop_product_file'); el.val() ? configForm.submit() : el.effect('shake');",
            'class' => 'scalable primary',
        ]
    ];
}
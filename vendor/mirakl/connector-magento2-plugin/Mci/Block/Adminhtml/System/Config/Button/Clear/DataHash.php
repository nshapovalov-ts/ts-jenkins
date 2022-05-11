<?php
namespace Mirakl\Mci\Block\Adminhtml\System\Config\Button\Clear;

use Mirakl\Connector\Block\Adminhtml\System\Config\Button\AbstractClearButton;
use Mirakl\Mci\Helper\Hash;

class DataHash extends AbstractClearButton
{
    /**
     * {@inheritdoc}
     */
    protected function getTableName()
    {
        return Hash::TABLE_NAME;
    }
}
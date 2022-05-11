<?php
namespace Retailplace\Whitelistemaildomain\Model;

class Whitelistemaildomain extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Retailplace\Whitelistemaildomain\Model\ResourceModel\Whitelistemaildomain');
    }
}
?>
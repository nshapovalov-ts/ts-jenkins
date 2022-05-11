<?php
namespace Retailplace\Whitelistemaildomain\Model\ResourceModel;

class Whitelistemaildomain extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('onboarding_whitelist_email_domain', 'domain_id');
    }
}
?>
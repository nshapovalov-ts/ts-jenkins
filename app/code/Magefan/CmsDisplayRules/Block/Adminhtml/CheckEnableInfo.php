<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Block\Adminhtml;

/**
 * Class CheckEnableInfo block
 */
class CheckEnableInfo extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magefan\CmsDisplayRules\Model\Config
     */
    protected $config;

    /**
     * CheckEnableInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magefan\CmsDisplayRules\Model\Config $config
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magefan\CmsDisplayRules\Model\Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        foreach ($this->_storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                if (count($stores) == 0) {
                    continue;
                }

                foreach ($stores as $store) {

                    if ($this->config->isEnabled($store->getId())) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}

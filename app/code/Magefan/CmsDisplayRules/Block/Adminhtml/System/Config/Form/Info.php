<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Block\Adminhtml\System\Config\Form;

/**
 * Class Info block
 */
class Info extends \Magefan\Community\Block\Adminhtml\System\Config\Form\Info
{
	/**
     * Return extension url
     * @return string
     */
    protected function getModuleUrl()
    {
        return 'https://mage' . 'fan.com/magento-2-cms-display-rules-extension?utm_source=m2admin_extension_config&utm_medium=link&utm_campaign=regular';
    }

    /**
     * Return extension title
     * @return string
     */
    protected function getModuleTitle()
    {
        return 'CMS Display Rules Extension';
    }
}

<?php

namespace Magecomp\Base\Model\Admin\Notification;

use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Security\Model\ResourceModel\AdminSessionInfo\Collection;
use Magento\Framework\Module\ModuleListInterface;

class Versions implements MessageInterface
{

    protected $backendUrl;
    protected $authSession;
    private $adminSessionInfoCollection;
    protected $_moduleList;

    public function __construct(
        Collection $adminSessionInfoCollection,
        UrlInterface $backendUrl,
        Session $authSession,
        ModuleListInterface $moduleList
    )
    {
        $this->authSession = $authSession;
        $this->backendUrl = $backendUrl;
        $this->adminSessionInfoCollection = $adminSessionInfoCollection;
        $this->_moduleList = $moduleList;
    }

    public function getText()
    {
        $url =$this->backendUrl->getUrl('adminhtml/system_config/edit', ['section' => "adminnotify"]);
        $link = "<a href='$url'>Update Now</a>";
        $message = __('One or More MageComp Extensions have New Versions Available. '.$link);
        return $message;
    }


    public function getIdentity()
    {
        return sha1('magecomp_versions' . $this->authSession->getUser()->getLogdate());
    }

    public function isDisplayed()
    {
        try {
            $var=false;
            $allModule = $this->_moduleList->getAll();
            $xml = simplexml_load_file("http://magecomp.com/basepkg/notification/magecomp_extensions.xml");
            foreach ($xml->extension as $extension) {
                foreach ($allModule as $module) {
                    if (isset($module['setup_version'])) {
                        if (strpos($module['name'], "Magecomp") !== false) {
                            if ($module['name'] == $extension->name && strval($module['setup_version']) < strval($extension->setup_version)) {
                                $var=true;
                            }
                        }
                    }
                }
            }
            return $var;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function getSeverity()
    {
        return \Magento\Framework\Notification\MessageInterface::SEVERITY_CRITICAL;
    }

}

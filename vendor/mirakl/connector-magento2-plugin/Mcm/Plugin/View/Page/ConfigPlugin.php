<?php
namespace Mirakl\Mcm\Plugin\View\Page;

use Magento\Framework\View\Page\Config as PageConfig;
use Mirakl\Mcm\Helper\Config;

class ConfigPlugin
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $addBodyClassTriggers = [];

    /**
     * @param   Config  $config
     * @param   array   $addBodyClassTriggers
     */
    public function __construct(Config $config, $addBodyClassTriggers = [])
    {
        $this->config = $config;
        $this->addBodyClassTriggers = $addBodyClassTriggers;
    }

    /**
     * @param   PageConfig  $pageConfig
     * @param   \Closure    $proceed
     * @param   string      $className
     * @return  PageConfig
     */
    public function aroundAddBodyClass(PageConfig $pageConfig, \Closure $proceed, $className)
    {
        $proceed($className);

        if (in_array($className, $this->addBodyClassTriggers)) {
            $proceed(sprintf('mirakl-mcm-%s', $this->config->isMcmEnabled() ? 'on' : 'off'));
        }

        return $pageConfig;
    }
}
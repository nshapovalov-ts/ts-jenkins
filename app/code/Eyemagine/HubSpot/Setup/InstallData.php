<?php
/**
 * EYEMAGINE - The leading Magento Solution Partner
 *
 * HubSpot Integration with Magento
 *
 * @author    EYEMAGINE <magento@eyemaginetech.com>
 * @copyright Copyright (c) 2016 EYEMAGINE Technology, LLC (http://www.eyemaginetech.com)
 * @license   http://www.eyemaginetech.com/license
 */
namespace Eyemagine\HubSpot\Setup;

use Magento\Framework\Module\Setup\Migration;
use Eyemagine\HubSpot\Model\Config\Backend\Keys;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class InstallData
 *
 * @package Eyemagine\HubSpot\Setup
 */
class InstallData implements InstallDataInterface
{

    /**
     *
     * @var Eyemagine\HubSpot\Model\Config\Backend\Keys Keys
     */
    protected $keys;

    public function __construct(Keys $keys)
    {
        $this->keys = $keys;
    }

    /**
     * Installs DB schema for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->keys->generateAccessKeys();
        
        $setup->endSetup();
    }
}

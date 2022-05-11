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
namespace Eyemagine\HubSpot\Model\Config\Backend;

use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class Keys
 *
 * @package Eyemagine\HubSpot\Model\Config\Backend
 */
class Keys extends \Magento\Framework\App\Config\Value
{

    const XML_PATH_EYEMAGINE_HUBSPOT_USER_KEY = 'eyehubspot/settings/userkey';

    const XML_PATH_EYEMAGINE_HUBSPOT_PASS_CODE = 'eyehubspot/settings/passcode';

    const CONFIG_SCOPE = 'default';

    const CONFIG_SCOPE_ID = 0;

    /**
     *
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $configWriter;

    /**
     *
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public function __construct(WriterInterface $configWriter)
    {
        $this->configWriter = $configWriter;
    }

    /**
     * Writes random access keys to the system config
     */
    public function generateAccessKeys()
    {
        $userkey = substr(hash('sha256', \date('YmdHis') . rand(0, 32767) . self::CONFIG_SCOPE), 5, 32);
        $passcode = substr(hash('sha256', (rand(0, 32767) * (17 + self::CONFIG_SCOPE_ID)) . \date('YmdHis') . 'eyehubspot'), 7, 32);
        
        $this->configWriter->save(
            self::XML_PATH_EYEMAGINE_HUBSPOT_USER_KEY,
            $userkey,
            self::CONFIG_SCOPE,
            self::CONFIG_SCOPE_ID
        );
        
        $this->configWriter->save(
            self::XML_PATH_EYEMAGINE_HUBSPOT_PASS_CODE,
            $passcode,
            self::CONFIG_SCOPE,
            self::CONFIG_SCOPE_ID
        );
    }
}

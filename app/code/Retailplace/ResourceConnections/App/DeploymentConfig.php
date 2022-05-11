<?php
/**
 * Retailplace_ResourceConnection
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ResourceConnections\App;

use Magento\Framework\App\DeploymentConfig\Reader;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\App\DeploymentConfig as MagentoDeploymentConfig;

/**
 * Class DeploymentConfig implements config reader
 */
class DeploymentConfig extends MagentoDeploymentConfig
{
    /** Slave section */
    const SLAVE_CONNECTION = 'slave_connection';

    /** @var Http */
    protected $request;

    /**
     * @param Reader $reader
     * @param Http $requestHttp
     * @param array $overrideData
     */
    public function __construct(
        Reader $reader,
        Http $requestHttp,
        array $overrideData = []
    ) {
        $this->request = $requestHttp;
        parent::__construct($reader, $overrideData);
    }

    /**
     * @param $key
     * @param $defaultValue
     * @return array|null
     */
    public function get($key = null, $defaultValue = null)
    {
        if ($this->request->isSafeMethod()) {
            $rule = '/^' . preg_quote(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS, '/') . '/';
            if (preg_match($rule, $key)) {
                $slaveConfigurationPath = str_replace('connection', self::SLAVE_CONNECTION, $key);
                $config = parent::get($key, $defaultValue);
                $config['slave'] = parent::get($slaveConfigurationPath, $defaultValue);
                return $config;
            }
        }

        return parent::get($key, $defaultValue);
    }
}

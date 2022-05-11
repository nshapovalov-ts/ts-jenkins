<?php
namespace Mirakl\Api\Helper;

use Mirakl\Api\Model\Client\ClientManager;
use Mirakl\Api\Model\Log\LogOptions;

class Config extends \Mirakl\Core\Helper\Config
{
    const XML_PATH_ENABLE          = 'mirakl_api/general/enable';
    const XML_PATH_API_KEY         = 'mirakl_api/general/api_key';
    const XML_PATH_API_URL         = 'mirakl_api/general/api_url';
    const XML_PATH_CONNECT_TIMEOUT = 'mirakl_api/general/connect_timeout';

    const XML_PATH_API_DEVELOPER_LOG_OPTION = 'mirakl_api_developer/log/log_option';
    const XML_PATH_API_DEVELOPER_LOG_FILTER = 'mirakl_api_developer/log/log_filter';

    /**
     * @var bool
     */
    protected $apiEnabled = true;

    /**
     * @return  $this
     */
    public function disable()
    {
        ClientManager::disable();

        return $this->setApiEnabled(false);
    }

    /**
     * @return  $this
     */
    public function enable()
    {
        ClientManager::enable();

        return $this->setApiEnabled(true);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function isApiLogEnabled($store = null)
    {
        return $this->getApiLogOption($store) !== LogOptions::LOG_DISABLED;
    }

    /**
     * @return  bool
     */
    public function isEnabled()
    {
        return $this->apiEnabled
            && $this->getFlag(self::XML_PATH_ENABLE)
            && $this->getApiUrl()
            && $this->getApiKey();
    }

    /**
     * @return  string
     */
    public function getApiKey()
    {
        return $this->getValue(self::XML_PATH_API_KEY);
    }

    /**
     * @param   mixed   $store
     * @return  int
     */
    public function getApiLogOption($store = null)
    {
        return (int) $this->getValue(self::XML_PATH_API_DEVELOPER_LOG_OPTION, $store);
    }

    /**
     * @param   mixed   $store
     * @return  string
     */
    public function getApiLogFilter($store = null)
    {
        return $this->getValue(self::XML_PATH_API_DEVELOPER_LOG_FILTER, $store);
    }

    /**
     * @return  string
     */
    public function getApiUrl()
    {
        return $this->getValue(self::XML_PATH_API_URL);
    }

    /**
     * @return  int
     */
    public function getConnectTimeout()
    {
        return (int) $this->getValue(self::XML_PATH_CONNECT_TIMEOUT);
    }

    /**
     * Enable or disable API
     *
     * @param   bool    $flag
     * @return  $this
     */
    protected function setApiEnabled($flag)
    {
        $this->apiEnabled = (bool) $flag;

        return $this;
    }
}

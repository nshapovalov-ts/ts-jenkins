<?php
namespace Mirakl\Api\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Mirakl\Api\Helper\Config;

class ApiLogNotification implements MessageInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param   Config          $config
     * @param   UrlInterface    $urlBuilder
     */
    public function __construct(Config $config, UrlInterface $urlBuilder)
    {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity()
    {
        return 'MIRAKL_API_LOG_ENABLED';
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayed()
    {
        return $this->config->isApiLogEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function getText()
    {
        $url = $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/mirakl_api_developer');

        return __('<strong>Mirakl API logging is enabled. It is not recommended to enable it in a production environment.</strong><br>'.
            'Go to <a href="%1">developer configuration</a> to disable it.', $url);
    }

    /**
     * {@inheritdoc}
     */
    public function getSeverity()
    {
        return self::SEVERITY_NOTICE;
    }
}

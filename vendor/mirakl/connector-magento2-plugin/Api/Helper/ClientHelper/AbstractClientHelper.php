<?php
namespace Mirakl\Api\Helper\ClientHelper;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mirakl\Api\Model\Client\ClientManager;
use Mirakl\Api\Model\Log\LoggerManager;
use Mirakl\Api\Model\Log\RequestLogValidator;
use Mirakl\Core\Client\AbstractApiClient;
use Mirakl\Core\Request\AbstractFileRequest;
use Mirakl\Core\Request\AbstractRequest;

/**
 * @method string getLastRequestString()
 */
abstract class AbstractClientHelper extends AbstractHelper
{
    /**
     * @var ClientManager
     */
    private $clientManager;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var LoggerManager
     */
    protected $loggerManager;

    /**
     * @var RequestLogValidator
     */
    protected $requestLogValidator;

    /**
     * @param   Context                $context
     * @param   ClientManager          $clientManager
     * @param   CacheInterface         $cache
     * @param   LoggerManager          $loggerManager
     * @param   RequestLogValidator    $requestLogValidator
     */
    public function __construct(
        Context $context,
        ClientManager $clientManager,
        CacheInterface $cache,
        LoggerManager $loggerManager,
        RequestLogValidator $requestLogValidator
    ) {
        parent::__construct($context);

        $this->clientManager       = $clientManager;
        $this->cache               = $cache;
        $this->loggerManager       = $loggerManager;
        $this->requestLogValidator = $requestLogValidator;
    }

    /**
     * Proxy to API client methods
     *
     * @param   string  $name
     * @param   array   $args
     * @return  mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array([$this->getClient(), $name], $args);
    }

    /**
     * @return  string
     */
    abstract protected function getArea();

    /**
     * Get Api Client
     *
     * @return  AbstractApiClient
     */
    public function getClient()
    {
        return $this->clientManager->get($this->getArea());
    }

    /**
     * Sends specified request of type 'file' to Mirakl API
     *
     * @param   AbstractFileRequest $request
     * @return  mixed
     */
    public function upload(AbstractFileRequest $request)
    {
        return $this->post($request);
    }

    /**
     * Sends specified request to Mirakl API
     *
     * @param   AbstractRequest $request
     * @return  mixed
     */
    public function post(AbstractRequest $request)
    {
        return $this->send($request);
    }

    /**
     * @param   AbstractRequest $request
     * @param   bool            $raw
     * @return  mixed
     */
    public function send(AbstractRequest $request, $raw = false)
    {
        $client = $this->getClient();
        $client->raw((bool) $raw);

        if ($this->requestLogValidator->validate($request)) {
            $logger = $this->loggerManager->getLogger();
            $messageFormatter = $this->loggerManager->getMessageFormatter();
            $client->setLogger($logger, $messageFormatter);
        }

        $this->_eventManager->dispatch('mirakl_api_send_request_before', [
            'client'  => $client,
            'request' => $request,
            'helper'  => $this,
        ]);

        return $client($request);
    }
}
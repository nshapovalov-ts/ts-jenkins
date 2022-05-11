<?php
namespace Mirakl\Api\Model\Client;

use Mirakl\Core\Client\AbstractApiClient;

class ClientManager
{
    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var AbstractApiClient[]
     */
    private static $clients = [];

    /**
     * @param   ClientFactory   $clientFactory
     */
    public function __construct(ClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * Disable all API clients
     */
    public static function disable()
    {
        foreach (self::$clients as $client) {
            $client->disable();
        }
    }

    /**
     * Enable all API clients
     */
    public static function enable()
    {
        foreach (self::$clients as $client) {
            $client->disable(false);
        }
    }

    /**
     * @param   string  $area
     * @return  AbstractApiClient
     */
    public function get($area)
    {
        if (!isset(self::$clients[$area])) {
            self::$clients[$area] = $this->clientFactory->create($area);
        }

        return self::$clients[$area];
    }
}

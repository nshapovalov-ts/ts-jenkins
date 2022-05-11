<?php
namespace Mirakl\Api\Model\Client;

use Magento\Framework\App\ProductMetadataInterface;
use Mirakl\Api\Helper\Config;
use Mirakl\Core\Client\AbstractApiClient;
use Mirakl\Core\Helper\Data as CoreHelper;

class ClientFactory
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @param   Config                      $config
     * @param   CoreHelper                  $coreHelper
     * @param   ProductMetadataInterface    $productMetadata
     */
    public function __construct(
        Config $config,
        CoreHelper $coreHelper,
        ProductMetadataInterface $productMetadata
    ) {
        $this->config = $config;
        $this->coreHelper = $coreHelper;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param   string  $area
     * @return  AbstractApiClient
     */
    public function create($area)
    {
        $apiUrl = $this->config->getApiUrl();
        $apiKey = $this->config->getApiKey();

        switch ($area) {
            case 'MMP':
                $instanceName = \Mirakl\MMP\Front\Client\FrontApiClient::class;
                break;
            case 'MCI':
                $instanceName = \Mirakl\MCI\Front\Client\FrontApiClient::class;
                break;
            case 'MCM':
                $instanceName = \Mirakl\MCM\Front\Client\FrontApiClient::class;
                break;
            default:
                throw new \InvalidArgumentException('Could not create API client for area ' . $area);
        }

        $client = new $instanceName($apiUrl, $apiKey);
        $this->init($client);

        return $client;
    }

    /**
     * @param AbstractApiClient $client
     */
    private function init(AbstractApiClient $client)
    {
        // Customize User-Agent
        $userAgent = sprintf(
            'Magento-%s/%s Mirakl-Magento-Connector/%s %s',
            $this->productMetadata->getEdition(),
            $this->productMetadata->getVersion(),
            $this->coreHelper->getVersion(),
            AbstractApiClient::getDefaultUserAgent()
        );
        $client->setUserAgent($userAgent);

        // Add a connection timeout
        $client->addOption('connect_timeout', $this->config->getConnectTimeout());

        // Disable API calls if needed
        if (!$this->config->isEnabled()) {
            $client->disable();
        }
    }
}

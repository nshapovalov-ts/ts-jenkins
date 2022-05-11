<?php
namespace Mirakl\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mirakl\Api\Helper\Shop as Api;
use Mirakl\Core\Model\Shop as ShopModel;
use Mirakl\Core\Model\ResourceModel\ShopFactory as ShopResourceFactory;
use Mirakl\Core\Model\ShopFactory;
use Mirakl\Process\Model\Process;

class Shop extends AbstractHelper
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ShopFactory
     */
    protected $shopFactory;

    /**
     * @var ShopResourceFactory
     */
    protected $shopResourceFactory;

    /**
     * @param   Context             $context
     * @param   Api                 $api
     * @param   Config              $config
     * @param   ShopFactory         $shopFactory
     * @param   ShopResourceFactory $shopResourceFactory
     */
    public function __construct(
        Context $context,
        Api $api,
        Config $config,
        ShopFactory $shopFactory,
        ShopResourceFactory $shopResourceFactory
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->config = $config;
        $this->shopFactory = $shopFactory;
        $this->shopResourceFactory = $shopResourceFactory;
    }

    /**
     * Get Configuration helper
     *
     * @return Config $connectorConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param   Process         $process
     * @param   \DateTime|null  $since
     * @return  int
     * @throws  \Exception
     */
    public function synchronize(Process $process, $since = null)
    {
        $shops = $this->api->getAllShops($since);

        if ($shops->count() > 0) {
            $process->output(__('Synchronizing shops...'));
            $this->shopResourceFactory->create()->synchronize($shops, $process);
            $process->output(__('Shops have been synchronized successfully.'));
        } else {
            if ($since) {
                $process->output(__('No shop to synchronize since %1.', $since->format('Y-m-d H:i:s')));
            } else {
                $process->output(__('No shop to synchronize.'));
            }
        }

        return $shops->count();
    }

    /**
     * Retrieve shop based on given shop id
     *
     * @param   int $shopId
     * @return  ShopModel
     */
    public function getShopById($shopId)
    {
        $shop = $this->shopFactory->create();
        $this->shopResourceFactory->create()->load($shop, $shopId);

        return $shop;
    }
}

<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Plugin\Helper;

use DateTime;
use Exception;
use Mirakl\Process\Model\Process;
use Mirakl\Api\Helper\Shop as Api;
use Mirakl\Connector\Helper\Shop as MiraklConnectorShop;
use Retailplace\MiraklShop\Model\Synchronizer\ShopUpdater;

/**
 * Class Shop around plugin
 */
class Shop
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @var ShopUpdater
     */
    private $shopUpdater;

    /**
     * @param Api $api
     * @param ShopUpdater $shopUpdater
     */
    public function __construct(
        Api $api,
        ShopUpdater $shopUpdater
    ) {
        $this->api = $api;
        $this->shopUpdater = $shopUpdater;
    }

    /**
     * Around plugin to change synchronize() call from resource model to method in ShopUpdater
     *
     * @param   Process $process
     * @param   DateTime|null  $since
     * @return  int
     * @throws  Exception
     */
    public function aroundSynchronize(MiraklConnectorShop $subject, callable $proceed, $process, $since = null)
    {
        $shops = $this->api->getAllShops($since);
        if ($shops->count() > 0) {
            $process->output(__('Synchronizing shops...'));
            $this->shopUpdater->synchronize($shops, $process);
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
}

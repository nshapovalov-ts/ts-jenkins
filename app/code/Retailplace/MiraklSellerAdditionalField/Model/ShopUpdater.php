<?php
/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Model;

use Mirakl\Core\Model\ResourceModel\Shop\Collection as ShopCollection;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Retailplace\SellerTags\Api\Data\SellerTagsAttributes;
use Mirakl\Core\Model\Shop;
use Psr\Log\LoggerInterface;
use Magento\Framework\DB\Select;
use Zend_Db_Select;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;

/**
 * Class ShopUpdater
 */
class ShopUpdater
{
    /**
     * @var ShopCollection
     */
    private $shopCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OfferCollectionFactory
     */
    private $offerCollectionFactory;

    /**
     * @param ShopCollectionFactory $shopCollectionFactory
     * @param OfferCollectionFactory $offerCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ShopCollectionFactory $shopCollectionFactory,
        OfferCollectionFactory $offerCollectionFactory,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->offerCollectionFactory = $offerCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Update Lead Time To Ship
     *
     * @param array $skus
     * @return array
     */
    public function updateLeadtimeToShip(array $skus = []): array
    {
        $updated = [];
        $shopIds = [];

        try {
            $shopCollection = $this->shopCollectionFactory->create();
            $connection = $shopCollection->getConnection();

            $select = $shopCollection->getSelect();
            $select->reset(Select::COLUMNS);
            $select->columns([
                'id'                   => 'main_table.id',
                'new_leadtime_to_ship' => 'MAX(IF(offer.leadtime_to_ship is not null AND offer.leadtime_to_ship != "",offer.leadtime_to_ship,0))',
                'leadtime_to_ship'     => 'IF(main_table.leadtime_to_ship is not null, main_table.leadtime_to_ship, 0)'
            ]);

            $select->joinInner(['offer' => $connection->getTableName('mirakl_offer')], 'offer.shop_id = main_table.id', []);
            $select->where('offer.deleted = "false"');
            $select->group('main_table.id');
            $select->having('new_leadtime_to_ship != leadtime_to_ship');

            if ($skus) {
                $offersCollection = $this->offerCollectionFactory->create();
                $selectShops = $offersCollection->getSelect();
                $selectShops->reset(Zend_Db_Select::COLUMNS);
                $selectShops->columns(['main_table.shop_id']);
                $selectShops->where('main_table.product_sku in (?)', $skus);
                $selectShops->group('main_table.shop_id');

                foreach ($offersCollection as $item) {
                    $shopIds[$item->getShopId()] = $item->getShopId();
                }

                if (!$shopIds) {
                    return $updated;
                }

                $select->where('main_table.id in (?)', $shopIds);
            }



            /** @var Shop $shop */
            foreach ($shopCollection->getItems() as $shop) {
                try {
                    $newValue = $shop->getNewLeadtimeToShip();
                    $oldValue = $shop->getLeadtimeToShip();

                    $this->resourceConnection->getConnection()->update(
                        $connection->getTableName('mirakl_shop'),
                        [SellerTagsAttributes::SHOP_LEADTIME_TO_SHIP => $newValue ?: null],
                        ['id = ?' => $shop->getId()]
                    );
                    $updated[$shop->getId()] = [
                        'new' => $newValue ?: "null",
                        'old' => $oldValue ?: "null"
                    ];
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $updated;
    }

}

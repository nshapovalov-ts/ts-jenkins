<?php
namespace Mirakl\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\Core\Exception\ShippingZoneNotFound;
use Mirakl\Core\Model\Shipping\ZoneFactory as ShippingZoneFactory;
use Mirakl\Core\Model\ResourceModel\Shipping\Zone\CollectionFactory as ShippingZoneCollectionFactory;
use Mirakl\Core\Model\ResourceModel\Shipping\Zone\Collection as ShippingZoneCollection;

class ShippingZone extends AbstractHelper
{
    /**
     * @var ShippingZoneFactory
     */
    protected $shippingZoneFactory;

    /**
     * @var ShippingZoneCollectionFactory
     */
    protected $shippingZoneCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param   Context                         $context
     * @param   ShippingZoneFactory             $shippingZoneFactory
     * @param   ShippingZoneCollectionFactory   $shippingZoneCollectionFactory
     * @param   StoreManagerInterface           $storeManager
     */
    public function __construct(
        Context $context,
        ShippingZoneFactory $shippingZoneFactory,
        ShippingZoneCollectionFactory $shippingZoneCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->shippingZoneFactory = $shippingZoneFactory;
        $this->shippingZoneCollectionFactory = $shippingZoneCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Returns shipping zone code matching specified address data
     *
     * @param   DataObject  $address
     * @param   mixed       $store
     * @return  string
     * @throws  ShippingZoneNotFound
     */
    public function getShippingZoneCode(DataObject $address, $store = null)
    {
        $zones = $this->getShippingZoneRules($store);
        foreach ($zones as $zone) {
            /** @var \Mirakl\Core\Model\Shipping\Zone $zone */
            if ($zone->getRule()->validate($address)) {
                return $zone->getCode();
            }
        }

        throw new ShippingZoneNotFound(__('No shipping zone found for current address'));
    }

    /**
     * Returns active shipping zone rules for current or specified store (sorted by priority)
     *
     * @param   mixed   $store
     * @return  ShippingZoneCollection
     */
    public function getShippingZoneRules($store = null)
    {
        $store = $this->storeManager->getStore($store);
        /** @var ShippingZoneCollection $zones */
        $zones = $this->shippingZoneCollectionFactory->create();
        $zones->addActiveFilter()
            ->addStoreFilter($store->getId())
            ->setSortOrder();

        return $zones;
    }
}
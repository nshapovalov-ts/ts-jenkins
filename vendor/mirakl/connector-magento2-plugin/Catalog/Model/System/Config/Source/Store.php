<?php
namespace Mirakl\Catalog\Model\System\Config\Source;

use Magento\Store\Model\StoreManagerInterface;

class Store
{
    /**
     * @var array|null
     */
    private $options;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param   StoreManagerInterface   $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @return  array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [[
                'value' => '',
                'label' => __('-- Please Select --'),
            ]];

            $websites = $this->storeManager->getWebsites();
            foreach ($websites as $website) {
                /** @var \Magento\Store\Model\Website $website */
                $stores = [];
                foreach ($website->getStores() as $store) {
                    /** @var \Magento\Store\Model\Store $store */
                    $stores[] = [
                        'value' => $store->getId(),
                        'label' => $store->getName(),
                    ];
                }
                $this->options[] = [
                    'value' => $stores,
                    'label' => $website->getName(),
                ];
            }
        }

        return $this->options;
    }
}

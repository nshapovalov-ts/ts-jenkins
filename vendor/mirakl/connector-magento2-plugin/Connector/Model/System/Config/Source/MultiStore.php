<?php
namespace Mirakl\Connector\Model\System\Config\Source;

use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class MultiStore
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
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param   StoreManagerInterface   $storeManager
     * @param   ScopeConfigInterface    $config
     */
    public function __construct(StoreManagerInterface $storeManager, ScopeConfigInterface $config)
    {
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @return  array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [];

            $websites = $this->storeManager->getWebsites();
            foreach ($websites as $website) {
                /** @var \Magento\Store\Model\Website $website */
                $stores = [];
                foreach ($website->getStores() as $store) {
                    /** @var \Magento\Store\Model\Store $store */
                    $stores[] = [
                        'value' => $store->getId(),
                        'label' => sprintf('%s (%s)', $store->getName(), $this->config->getValue(
                            Custom::XML_PATH_GENERAL_LOCALE_CODE,
                            ScopeInterface::SCOPE_STORE,
                            $store->getId()
                        )),
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

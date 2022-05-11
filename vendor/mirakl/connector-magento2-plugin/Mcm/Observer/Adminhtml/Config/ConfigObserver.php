<?php
namespace Mirakl\Mcm\Observer\Adminhtml\Config;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Mirakl\Catalog\Helper\Config as CatalogConfigHelper;
use Mirakl\Mcm\Helper\Config as McmConfigHelper;

class ConfigObserver implements ObserverInterface
{
    /**
     * @var CatalogConfigHelper
     */
    protected $catalogConfigHelper;

    /**
     * @var McmConfigHelper
     */
    protected $mcmConfigHelper;

    /**
     *  @var ConfigWriterInterface
     */
    protected $configWriter;

    /**
     *  @var ManagerInterface
     */
    protected $messageManager;

    /**
     *  @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @param   CatalogConfigHelper     $catalogConfigHelper
     * @param   McmConfigHelper         $mcmConfigHelper
     * @param   ConfigWriterInterface   $configWriter
     * @param   ManagerInterface        $messageManager
     * @param   TypeListInterface       $cacheTypeList
     */
    public function __construct(
        CatalogConfigHelper $catalogConfigHelper,
        McmConfigHelper $mcmConfigHelper,
        ConfigWriterInterface $configWriter,
        ManagerInterface $messageManager,
        TypeListInterface $cacheTypeList
    ) {
        $this->catalogConfigHelper = $catalogConfigHelper;
        $this->mcmConfigHelper = $mcmConfigHelper;
        $this->configWriter = $configWriter;
        $this->messageManager = $messageManager;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @param   string  $message
     * @return  ManagerInterface
     */
    private function addWarningMessage($message)
    {
        return $this->messageManager->addWarningMessage(__($message));
    }

    /**
     * @return  void
     */
    private function cleanConfigCache()
    {
        $this->cacheTypeList->cleanType('config');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(EventObserver $observer)
    {
        if ($this->mcmConfigHelper->isMcmEnabled()) {
            if ($this->catalogConfigHelper->isSyncCategories()) {
                $this->saveConfig(CatalogConfigHelper::XML_PATH_ENABLE_SYNC_CATEGORIES, '0');
                $this->addWarningMessage('MCM configuration is enabled: Mirakl automatically disabled Marketplace Categories Synchronization (CA01)');
                $this->cleanConfigCache();
            }
            if ($this->catalogConfigHelper->isSyncProducts()) {
                $this->saveConfig(CatalogConfigHelper::XML_PATH_ENABLE_SYNC_PRODUCTS, '0');
                $this->addWarningMessage('MCM configuration is enabled: Mirakl automatically disabled Products Synchronization (P21)');
                $this->cleanConfigCache();
            }
            if (!$this->mcmConfigHelper->isSyncMcmProducts()) {
                $this->saveConfig(McmConfigHelper::XML_PATH_ENABLE_SYNC_MCM_PRODUCTS, '1');
                $this->addWarningMessage('MCM configuration is enabled: Mirakl automatically enabled MCM Products Export (CM21)');
                $this->cleanConfigCache();
            }
        } elseif ($this->mcmConfigHelper->isSyncMcmProducts()) {
            $this->saveConfig(McmConfigHelper::XML_PATH_ENABLE_SYNC_MCM_PRODUCTS, '0');
            $this->addWarningMessage('MCM configuration is disabled: Mirakl automatically disabled MCM Products Export (CM21)');
            $this->cleanConfigCache();
        }
    }

    /**
     * @param   string  $path
     * @param   mixed   $value
     * @return  void
     */
    private function saveConfig($path, $value)
    {
        $this->configWriter->save($path, $value);
    }
}
<?php
namespace Mirakl\Mci\Observer\Product\AttributeSet;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\Mci\Helper\Attribute as AttributeHelper;
use Mirakl\Mci\Helper\Config as MciConfigHelper;

abstract class AbstractObserver implements ObserverInterface
{
    /**
     * @var ApiConfig
     */
    protected $apiConfigHelper;

    /**
     * @var MciConfigHelper
     */
    protected $mciConfigHelper;

    /**
     * @var AttributeHelper
     */
    protected $attributeHelper;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param   ApiConfig           $apiConfigHelper
     * @param   MciConfigHelper     $mciConfigHelper
     * @param   AttributeHelper     $attributeHelper
     * @param   ManagerInterface    $messageManager
     */
    public function __construct(
        ApiConfig $apiConfigHelper,
        MciConfigHelper $mciConfigHelper,
        AttributeHelper $attributeHelper,
        ManagerInterface $messageManager
    ) {
        $this->apiConfigHelper = $apiConfigHelper;
        $this->mciConfigHelper = $mciConfigHelper;
        $this->attributeHelper = $attributeHelper;
        $this->messageManager  = $messageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->apiConfigHelper->isEnabled() && $this->mciConfigHelper->isSyncAttributes()) {
            try {
                // Export attributes tree when saving or deleting an attribute set
                $this->attributeHelper->exportTree();
            } catch (\Exception $e) {
                $this->messageManager->addWarningMessage(
                    __('An error occurred while contacting Mirakl: %1', $e->getMessage())
                );
            }
        }
    }
}

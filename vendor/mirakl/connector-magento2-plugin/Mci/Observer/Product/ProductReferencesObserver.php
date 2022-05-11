<?php
namespace Mirakl\Mci\Observer\Product;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Catalog\Helper\Config as CatalogConfig;
use Mirakl\Mci\Helper\Config as MciConfig;

class ProductReferencesObserver implements ObserverInterface
{
    /**
     * @var CatalogConfig
     */
    private $catalogConfig;

    /**
     * @var MciConfig
     */
    private $mciConfig;

    /**
     * @param   CatalogConfig   $catalogConfig
     * @param   MciConfig       $mciConfig
     */
    public function __construct(CatalogConfig $catalogConfig, MciConfig $mciConfig)
    {
        $this->catalogConfig = $catalogConfig;
        $this->mciConfig = $mciConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getData('product');
        /** @var \Magento\Framework\DataObject $refs */
        $refs = $observer->getData('refs');

        $attributes = $this->catalogConfig->getIdentifiersAttributeCodes();
        $hasDeduplicationMultiValues = $this->mciConfig->isDeduplicationMultiValues();
        $delimiter = $this->mciConfig->getDeduplicationDelimiter();
        foreach ($attributes as $attribute) {
            if ($value = $product->getData($attribute)) {
                if ($hasDeduplicationMultiValues) {
                    $value = explode($delimiter, $value);
                }
                $refs->setData($attribute, $value);
            }
        }
    }
}
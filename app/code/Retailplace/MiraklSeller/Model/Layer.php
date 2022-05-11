<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Retailplace MiraklSeller Layer model class
 */
class Layer extends \Magento\Catalog\Model\Layer
{
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;
    protected $_offerModel;
    protected $request;
    /**
     * Layer constructor.
     * @param \Magento\Catalog\Model\Layer\ContextInterface $context
     * @param \Magento\Catalog\Model\Layer\StateFactory $layerStateFactory
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $catalogProduct
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Registry $registry
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CollectionFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\ContextInterface $context,
        \Magento\Catalog\Model\Layer\StateFactory $layerStateFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product $catalogProduct,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        CategoryRepositoryInterface $categoryRepository,
        CollectionFactory $productCollectionFactory,
        \Mirakl\Connector\Model\Offer $offerModel,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_offerModel = $offerModel;
        $this->request = $request;
        parent::__construct(
            $context,
            $layerStateFactory,
            $attributeCollectionFactory,
            $catalogProduct,
            $storeManager,
            $registry,
            $categoryRepository,
            $data
        );
    }

    /**
     * Get MiraklSeller product collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Zend_Date_Exception
     */
    public function getProductCollection()
    {

        //TODO Check, all places where it is used to update and remove this class

        if (isset($this->_productCollections['Retailplace_custom'])) {
            $collection = $this->_productCollections['Retailplace_custom'];
        } else {
            $sellerId = $this->request->getParam('id');
            $query = $this->request->getParam('q');

            $collection = $this->productCollectionFactory->create();
            $connection = $collection->getConnection();
            $eavOptionId = $connection->fetchOne($connection->select()->from(['ms' => 'mirakl_shop'], ['eav_option_id'])->where('id = ?', $sellerId)->limit(1));
            $shopId = $eavOptionId;
            if ($shopId) {
                $collection->addFieldToFilter('mirakl_shop_ids', $shopId);
                $collection->setFlag('has_shop_ids_filter', true);
                if ($query) {
                    $cloneCollection = (clone $collection)->addAttributeToFilter('name', ['like' => "%$query%"]);
                    $where = $cloneCollection->getSelect()->getPart('where');
                    foreach ($where as $key => $value) {
                        if (strpos($value, $query) !== false) {
                            $where[$key] = str_replace("at_name.value LIKE '%$query%'", "(at_name.value LIKE '%$query%') OR (`e`.`sku` LIKE '%$query%')", $value);
                        }
                    }
                    $cloneCollection->getSelect()->setPart('where', $where);
                    $allIds = $cloneCollection->getAllIds();

                    $collection->getSelect()->where("(e.entity_id  in (?))", $allIds);
                }
            }

            $this->prepareProductCollection($collection);
            $this->_productCollections['Retailplace_custom'] = $collection;
        }

        return $collection;
    }
}

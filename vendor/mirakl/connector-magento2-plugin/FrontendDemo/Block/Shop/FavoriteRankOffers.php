<?php
namespace Mirakl\FrontendDemo\Block\Shop;

use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Catalog\Block\Product\Image as ProductImage;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitationFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;

class FavoriteRankOffers extends View
{
    /**
     * @var ProductCollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var ProductCollection
     */
    protected $_productCollection;

    /**
     * @var ImageBuilder
     */
    protected $_imageBuilder;

    /**
     * @var CatalogConfig
     */
    protected $_catalogConfig;

    /**
     * @var ProductVisibility
     */
    protected $_productVisibility;

    /**
     * @var ProductStatus
     */
    protected $_productStatus;

    /**
     * @var integer
     */
    protected $_favoriteProductsLimit;

    /**
     * @var bool
     */
    protected $_isEnterprise;

    /**
     * @param   Context                     $context
     * @param   Registry                    $registry
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   ImageBuilder                $imageBuilder
     * @param   CatalogConfig               $catalogConfig
     * @param   ProductLimitationFactory    $productLimitationFactory
     * @param   ProductVisibility           $productVisibility
     * @param   ProductStatus               $productStatus
     * @param   integer                     $favoriteProductsLimit
     * @param   array                       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ProductCollectionFactory $productCollectionFactory,
        ImageBuilder $imageBuilder,
        CatalogConfig $catalogConfig,
        ProductCollection\ProductLimitationFactory $productLimitationFactory,
        ProductVisibility $productVisibility,
        ProductStatus $productStatus,
        $favoriteProductsLimit,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_imageBuilder = $imageBuilder;
        $this->_catalogConfig = $catalogConfig;
        $this->_productVisibility = $productVisibility;
        $this->_productStatus = $productStatus;
        $this->_favoriteProductsLimit = $favoriteProductsLimit;
        $this->_isEnterprise = \Mirakl\Core\Helper\Data::isEnterprise();
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->getTemplate()) {
            return '';
        }

        if ($this->_productCollection->count() == 0) {
            return '';
        }

        return $this->fetchView($this->getTemplateFile());
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->_setProductCollection();

        return $this;
    }

    /**
     * @return ProductCollection
     */
    public function getProductCollection()
    {
        return $this->_productCollection;
    }

    /**
     * @return int
     */
    public function getFavoriteProductsLimit()
    {
        return $this->_favoriteProductsLimit;
    }

    /**
     * Load and set the favorite rank products collection
     *
     * @return $this
     */
    protected function _setProductCollection()
    {
        $this->_productCollection = $this->_productCollectionFactory->create();

        $this->_productCollection->getSelect()
            ->joinInner(
                ['offer' => $this->_productCollection->getTable('mirakl_offer')],
                sprintf('e.sku = offer.product_sku AND offer.shop_id = %s', $this->getShop()->getId()),
                ['favorite_rank' => 'MIN(offer.favorite_rank)']
            )
            ->where('offer.favorite_rank > 0')
            ->group('e.entity_id')
            ->order('offer.favorite_rank ASC')
            ->limit($this->_favoriteProductsLimit);

        $this->_productCollection
            ->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
            ->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()])
            ->setVisibility($this->_productVisibility->getVisibleInSiteIds())
            ->addWebsiteFilter();

        foreach ($this->_getParentProducts() as $item) {
            $this->_productCollection->addItem($item);
        }

        $this->_sortCollectionByFavoriteRank();
        $this->_applyCollectionByLimit();

        return $this;
    }

    /**
     * Apply result's limit on the aggregated collection
     */
    protected function _applyCollectionByLimit()
    {
        if (empty($this->_productCollection)) {
            return;
        }

        $i = 0;
        foreach ($this->_productCollection as $key => $item) {
            if ($i++ < $this->_favoriteProductsLimit) {
                continue;
            }
            $this->_productCollection->removeItemByKey($key);
        }
    }

    /**
     * Sort the aggregated collection
     */
    protected function _sortCollectionByFavoriteRank()
    {
        if (empty($this->_productCollection)) {
            return;
        }

        $collection = [];
        foreach ($this->_productCollection as $item) {
            /** @var Product $item */
            $collection[] = $item;
        }

        usort($collection, function (Product $p1, Product $p2) {
            $a = $p1->getDataByKey('favorite_rank');
            $b = $p2->getDataByKey('favorite_rank');

            return $a == $b ? 0 : ($a > $b ? 1 : -1);
        });

        $this->_productCollection->removeAllItems();

        foreach ($collection as $item) {
            $this->_productCollection->addItem($item);
        }
    }

    /**
     * @return ProductCollection
     */
    protected function _getParentProducts()
    {
        $configuredProductCollection = $this->_productCollectionFactory->create();

        $select = $configuredProductCollection->getSelect()
            ->reset('columns')
            ->join(
                ['parent' => $configuredProductCollection->getTable('catalog_product_super_link')],
                'e.entity_id = parent.product_id',
                'parent_id' // refers to row_id in EE and entity_id in CE
            )
            ->join(
                ['offer' => $configuredProductCollection->getTable('mirakl_offer')],
                sprintf('e.sku = offer.product_sku AND offer.shop_id = %s', $this->getShop()->getId()),
                ['favorite_rank' => 'MIN(offer.favorite_rank)']
            )
            ->where('offer.favorite_rank > 0')
            ->group('parent.parent_id')
            ->order('offer.favorite_rank ASC')
            ->limit($this->_favoriteProductsLimit);

        $parentFavoriteRank = $configuredProductCollection->getConnection()->fetchPairs($select);
        $entityCol = $this->_isEnterprise ? 'row_id' : 'entity_id';

        $parentCollection = $this->_productCollectionFactory->create()
            ->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
            ->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()])
            ->setVisibility($this->_productVisibility->getVisibleInSiteIds())
            ->addAttributeToFilter($entityCol, ['in' => array_keys($parentFavoriteRank)]);

        /** @var Product $item */
        foreach ($parentCollection as $item) {
            if (isset($parentFavoriteRank[$item->getData($entityCol)])) {
                $item->setData('favorite_rank', $parentFavoriteRank[$item->getData($entityCol)]);
            }
        }

        return $parentCollection;
    }

    /**
     * {@inheritdoc}
     */
    protected function _setTabTitle()
    {
        $title = __('Favorite Products');
        $this->setTitle($title);
    }

    /**
     * Retrieve product image
     *
     * @param   Product         $product
     * @param   string          $imageId
     * @param   array           $attributes
     * @return  ProductImage
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        $image = $this->_imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();

        return $image;
    }

    /**
     * Retrieve Product URL using UrlDataObject
     *
     * @param   Product     $product
     * @param   array       $additional the route params
     * @return  string
     */
    public function getProductUrl($product, $additional = [])
    {
        $url = '#';
        if ($this->_hasProductUrl($product)) {
            if (!isset($additional['_escape'])) {
                $additional['_escape'] = true;
            }
            $url = $product->getUrlModel()->getUrl($product, $additional);
        }

        return $url;
    }

    /**
     * Check Product has URL
     *
     * @param   Product     $product
     * @return  bool
     */
    protected function _hasProductUrl($product)
    {
        return $product->getVisibleInSiteVisibilities()
            && !in_array(ProductVisibility::VISIBILITY_NOT_VISIBLE, $product->getVisibleInSiteVisibilities());
    }
}

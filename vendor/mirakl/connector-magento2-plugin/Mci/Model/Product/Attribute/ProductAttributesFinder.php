<?php
namespace Mirakl\Mci\Model\Product\Attribute;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttrSetCollectionFactory;
use Mirakl\Mci\Model\System\Config\Source\Attribute as AttributeSource;

class ProductAttributesFinder
{
    /**
     * @var AttributeSource
     */
    protected $attributeSource;

    /**
     * @var AttrSetCollectionFactory
     */
    protected $attrSetCollectionFactory;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @var ProductResourceFactory
     */
    protected $productResourceFactory;

    /**
     * Attributes grouped by set id
     *
     * @var array
     */
    protected $attributesBySetId = [];

    /**
     * @param   AttributeSource             $attributeSource
     * @param   ProductFactory              $productFactory
     * @param   ProductResourceFactory      $productResourceFactory
     * @param   AttrSetCollectionFactory    $attrSetCollectionFactory
     */
    public function __construct(
        AttributeSource $attributeSource,
        ProductFactory $productFactory,
        ProductResourceFactory $productResourceFactory,
        AttrSetCollectionFactory $attrSetCollectionFactory
    ) {
        $this->attributeSource = $attributeSource;
        $this->productFactory = $productFactory;
        $this->productResourceFactory = $productResourceFactory;
        $this->attrSetCollectionFactory = $attrSetCollectionFactory;
    }

    /**
     * @param   string  $attrCode
     * @return  Attribute|null
     */
    public function findByCode($attrCode)
    {
        $attributes = $this->getAttributesByCode();

        return isset($attributes[$attrCode]) ? $attributes[$attrCode] : null;
    }

    /**
     * @param   int $setId
     * @return  Attribute[]
     */
    public function findBySetId($setId)
    {
        $attributes = $this->getAttributesBySetId();

        return isset($attributes[$setId]) ? $attributes[$setId] : [];
    }

    /**
     * @return  Attribute[]
     */
    public function getAttributesByCode()
    {
        return $this->getProductResource()->getAttributesByCode();
    }

    /**
     * @return  Attribute[][]
     */
    public function getAttributesBySetId()
    {
        if (empty($this->attributesBySetId)) {
            $resource = $this->getProductResource();

            $collection = $this->attrSetCollectionFactory->create();
            $setIds = $collection->setEntityTypeFilter($resource->getTypeId())
                ->getAllIds();

            foreach ($setIds as $setId) {
                $this->attributesBySetId[$setId] = $resource->getSortedAttributes($setId);
            }
        }

        return $this->attributesBySetId;
    }

    /**
     * @return  Attribute[]
     */
    public function getExportableAttributes()
    {
        /** @var Attribute[] $attributes */
        $attributes = $this->attributeSource->getExportableAttributes()->getItems();

        return $attributes;
    }

    /**
     * @return  ProductResource
     */
    protected function getProductResource()
    {
        if (null === $this->productResource) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productFactory->create();

            /** @var ProductResource $resource */
            $this->productResource = $this->productResourceFactory->create();
            $this->productResource->loadAllAttributes($product);
        }

        return $this->productResource;
    }
}
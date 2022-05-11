<?php
namespace Mirakl\Mci\Test\Unit\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mirakl\Mci\Helper\Data as MciHelper;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /** @var MciHelper */
    protected $helper;

    /** @var Product|\PHPUnit_Framework_MockObject_MockObject */
    protected $product;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection|\PHPUnit_Framework_MockObject_MockObject */
    protected $productCollection;

    /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection|\PHPUnit_Framework_MockObject_MockObject */
    protected $attributeCollection;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    protected function setUp(): void
    {
        $this->product = $this->getMockBuilder('Magento\Catalog\Model\Product')
            ->setMethods(['getCollection', 'getResource', 'setStoreId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->product->expects($this->any())
            ->method('setStoreId')
            ->willReturnSelf();

        $productResource = $this->getMockBuilder('Magento\Catalog\Model\ResourceModel\Product')
            ->setMethods(['load', 'getAttribute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productResource->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $attributeMock = $this->getMockBuilder('Magento\Eav\Model\ResourceModel\Entity\Attribute')
            ->setMethods(['getId', 'getBackendTable'])
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        $attributeMock->expects($this->any())
            ->method('getBackendTable')
            ->willReturn('foo');
        $productResource->expects($this->any())
            ->method('getAttribute')
            ->willReturn($attributeMock);

        $this->product->expects($this->any())
            ->method('getResource')
            ->willReturn($productResource);

        $productFactory = $this->getMockBuilder('Magento\Catalog\Model\ProductFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $productFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->product);

        $this->initProductCollectionMock();

        $this->productCollection->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->product);

        $this->product->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->productCollection);

        $this->connection = $this->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $resource = $this->getMockBuilder('Magento\Framework\App\ResourceConnection')
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getTableName'])
            ->getMock();
        $resource->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);
        $resource->expects($this->any())
            ->method('getTableName')
            ->willReturnArgument(0);

        $eavEntityAttribute = $this->getMockBuilder('Magento\Eav\Model\ResourceModel\Entity\Attribute')
            ->disableOriginalConstructor()
            ->getMock();
        $eavEntityAttribute->expects($this->any())
            ->method('getIdByCode')
            ->willReturn(123);

        $eavConfig = $this->getMockBuilder('Magento\Eav\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \Magento\Eav\Model\Entity\Type|\PHPUnit_Framework_MockObject_MockObject $entityType */
        $entityType = $this->getMockBuilder('Magento\Eav\Model\Entity\Type')
            ->disableOriginalConstructor()
            ->getMock();

        $eavConfig->expects($this->any())
            ->method('getEntityType')
            ->with(Product::ENTITY)
            ->willReturn($entityType);

        $this->initAttributeCollectionMock();

        $entityType->expects($this->any())
            ->method('getAttributeCollection')
            ->willReturn($this->attributeCollection);

        $productResourceFactory = $this->getMockBuilder('Magento\Catalog\Model\ResourceModel\ProductFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $productResourceFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($productResource));

        $productCollectionFactory = $this->getMockBuilder('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $productCollectionFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->productCollection));

        $this->helper = (new ObjectManager($this))->getObject(MciHelper::class, [
            'productFactory' => $productFactory,
            'productResourceFactory' => $productResourceFactory,
            'productCollectionFactory' => $productCollectionFactory,
            'resource' => $resource,
            'eavEntityAttribute' => $eavEntityAttribute,
            'eavConfig' => $eavConfig
        ]);
    }

    protected function initAttributeCollectionMock()
    {
        $this->attributeCollection = $this->getMockBuilder('Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection')
            ->disableOriginalConstructor()
            ->getMock();

        $attribute1 = new DataObject(['attribute_code' => 'color', 'mirakl_is_variant' => true]);
        $attribute2 = new DataObject(['attribute_code' => 'mirakl_image_01', 'mirakl_is_variant' => false]);
        $attribute3 = new DataObject(['attribute_code' => 'mirakl_image_02', 'mirakl_is_variant' => false]);
        $attribute4 = new DataObject(['attribute_code' => 'size', 'mirakl_is_variant' => true]);
        $attribute5 = new DataObject(['attribute_code' => 'mirakl_shop_id', 'mirakl_is_variant' => false]);

        $this->attributeCollection->expects($this->any())
            ->method('getItems')
            ->willReturn([$attribute1, $attribute2, $attribute3, $attribute4, $attribute5]);
    }

    protected function initProductCollectionMock()
    {
        $this->productCollection = $this->getMockBuilder('Magento\Catalog\Model\ResourceModel\Product\Collection')
            ->setMethods(['addAttributeToFilter', 'addFieldToFilter', 'addAttributeToSelect',
                'setPage', 'count', 'getFirstItem', 'getSelect'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->with($this->equalTo('*'))
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('setPage')
            ->with($this->equalTo(1), $this->equalTo(1))
            ->willReturnSelf();
    }

    /**
     * @return  string|null
     */
    private function getProductShopsSkus()
    {
        return $this->product->getData(MciHelper::ATTRIBUTE_SHOPS_SKUS);
    }

    /**
     * @return  string|null
     */
    private function getProductShopsVariantIds()
    {
        return $this->product->getData(MciHelper::ATTRIBUTE_VARIANT_GROUP_CODES);
    }

    public function testAddProductShopSku()
    {
        $this->assertEmpty($this->getProductShopsSkus());
        $this->helper->addProductShopSku($this->product, '2001', 'MH06-S-Black');
        $this->assertEquals('2001|MH06-S-Black', $this->getProductShopsSkus());
        $this->helper->addProductShopSku($this->product, '2002', 'WS08-Small-Black');
        $this->assertEquals('2001|MH06-S-Black,2002|WS08-Small-Black', $this->getProductShopsSkus());
    }

    public function testAddProductShopVariantId()
    {
        $this->assertEmpty($this->getProductShopsVariantIds());
        $this->helper->addProductShopVariantId($this->product, '2001', 'VARIANT_GROUP_CODE');
        $this->assertEquals('2001|VARIANT_GROUP_CODE', $this->getProductShopsVariantIds());
    }

    public function testFindProductByAttribute()
    {
        $this->productCollection->expects($this->exactly(2))
            ->method('count')
            ->willReturnOnConsecutiveCalls(0, 1);

        $this->assertNull($this->helper->findProductByAttribute('mirakl_shop_id', '2001'));
        $this->assertEquals($this->product, $this->helper->findProductByAttribute('mirakl_shop_id', '2002', 'simple'));
    }

    public function testFindProductByMultiValues()
    {
        $this->productCollection->expects($this->exactly(2))
            ->method('count')
            ->willReturnOnConsecutiveCalls(0, 1);

        $this->assertNull($this->helper->findProductByMultiValues(MciHelper::ATTRIBUTE_SHOPS_SKUS, '2001|MH06-S-Black', ','));
        $this->assertEquals($this->product, $this->helper->findProductByMultiValues(MciHelper::ATTRIBUTE_SHOPS_SKUS, '2002|MH06-XL-White', ',', 'simple'));
    }

    public function testFindProductByShopSku()
    {
        $selectMock = $this->getMockBuilder('Magento\Framework\DB\Select')
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->any())
            ->method('columns')
            ->willReturn($selectMock);
        $selectMock->expects($this->any())
            ->method('join')
            ->willReturn($selectMock);
            $selectMock->expects($this->any())
            ->method('where')
            ->willReturn($selectMock);
        $selectMock->expects($this->any())
            ->method('limit')
            ->willReturn($selectMock);

        $this->productCollection->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);
        $this->connection->expects($this->any())
            ->method('fetchRow')
            ->with($selectMock)
            ->willReturnOnConsecutiveCalls(false, ['row_id' => 123456, 'entity_id' => 12345]);

        $this->assertNull($this->helper->findProductByShopSku('2001', 'MH06-S-Black'));
        $this->assertEquals($this->product, $this->helper->findProductByShopSku('2002', 'MH06-XL-White'));
    }

    public function testGetImagesAttributes()
    {
        $attributes = $this->helper->getImagesAttributes();

        $this->assertSame(['mirakl_image_01', 'mirakl_image_02'], array_keys($attributes));
    }

    public function testGetVariantAttributes()
    {
        $attributes = $this->helper->getVariantAttributes();

        $this->assertSame(['color', 'size'], array_keys($attributes));
    }

    public function testRemoveProductShopSku()
    {
        $this->product->setData(MciHelper::ATTRIBUTE_SHOPS_SKUS, '2001|MH06-S-Black,2002|WS08-Small-Black');
        $this->helper->removeProductShopSku($this->product, '2001', 'MH06-S-Black');
        $this->assertEquals('2002|WS08-Small-Black', $this->getProductShopsSkus());
        $this->helper->removeProductShopSku($this->product, '2002', 'WS08-Small-Black');
        $this->assertEmpty($this->getProductShopsSkus());
    }
}
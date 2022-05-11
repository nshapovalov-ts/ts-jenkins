<?php
namespace Mirakl\Mci\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mirakl\Catalog\Helper\Category as CategoryHelper;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    /** @var CategoryHelper */
    protected $categoryHelper;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    /** @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManager;

    /** @var \Magento\Store\Model\Store|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeMock;

    /** @var \Mirakl\Api\Helper\Category|\PHPUnit_Framework_MockObject_MockObject */
    protected $api;

    /** @var \Mirakl\Connector\Helper\Config|\PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var \Magento\Catalog\Model\ResourceModel\Category\TreeFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryTreeFactory;

    /** @var  \Magento\Catalog\Model\Category|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryMock;

    /** @var  \Magento\Catalog\Model\CategoryFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryFactory;

    /** @var  \Magento\Catalog\Model\ResourceModel\Category|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryResourceMock;

    /** @var  \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryResourceFactory;

    /** @var  \Magento\Catalog\Model\ResourceModel\Category\Collection|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryCollectionMock;

    /** @var  \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryCollectionFactory;

    /** @var \Magento\Framework\EntityManager\EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var \Magento\Framework\Data\Tree\Node\Collection|\PHPUnit_Framework_MockObject_MockObject */
    protected $nodeCollection;

    protected function setUp(): void
    {
        $this->storeMock = $this->getMockBuilder('Magento\Store\Model\Store')
            ->disableOriginalConstructor()
            ->setMethods(['getRootCategoryId'])
            ->getMock();
        $this->storeMock->expects($this->any())
            ->method('getRootCategoryId')
            ->willReturn(1);

        $this->storeManager = $this->getMockBuilder('Magento\Store\Model\StoreManagerInterface')->getMock();
        $this->storeManager->method('getDefaultStoreView')
            ->willReturn($this->storeMock);

        $this->storeManager->expects($this->any())
            ->method('getStores')
            ->willReturn([]);

        $this->api = $this->getMockBuilder('Mirakl\Api\Helper\Category')
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMockBuilder('Mirakl\Connector\Helper\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $this->config->expects($this->any())
            ->method('getCatalogIntegrationStore')
            ->willReturn($this->storeMock);
        $this->config->expects($this->any())
            ->method('getStoresForLabelTranslation')
            ->willReturn([]);

        $categoryTreeMock = $this->initCategoryTreeMock();

        $this->categoryTreeFactory = $this->getMockBuilder('Magento\Catalog\Model\ResourceModel\Category\TreeFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->categoryTreeFactory->expects($this->any())
            ->method('create')
            ->willReturn($categoryTreeMock);

        $this->categoryMock = $this->getMockBuilder('Magento\Catalog\Model\Category')
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'getLevel'])
            ->getMock();

        $collectionMock = $this->initCategoryCollectionMock();

        $this->categoryMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($collectionMock);

        $this->categoryFactory = $this->getMockBuilder('Magento\Catalog\Model\CategoryFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->categoryFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->categoryMock));

        $this->categoryResourceMock = $this->getMockBuilder('Magento\Catalog\Model\ResourceModel\Category')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryResourceFactory = $this->getMockBuilder('Magento\Catalog\Model\ResourceModel\CategoryFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->categoryResourceFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->categoryResourceMock));

        $this->categoryCollectionFactory = $this->getMockBuilder('Magento\Catalog\Model\ResourceModel\Category\CollectionFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->categoryCollectionFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($collectionMock));

        $this->entityManager = $this->getMockBuilder('Magento\Framework\EntityManager\EntityManager')
            ->setMethods(['load', 'save', 'delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventManagerMock = $this->getMockBuilder('Magento\Framework\Event\ManagerInterface')->getMock();

        $context = (new ObjectManager($this))->getObject(
            \Magento\Framework\App\Helper\Context::class,
            ['scopeConfig' => $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class)]
        );

        $this->categoryHelper = (new ObjectManager($this))->getObject(CategoryHelper::class, [
            'context' => $context,
            'storeManager' => $this->storeManager,
            'api' => $this->api,
            'config' => $this->config,
            'categoryTreeFactory' => $this->categoryTreeFactory,
            'categoryFactory' => $this->categoryFactory,
            'categoryResourceFactory' => $this->categoryResourceFactory,
            'categoryCollectionFactory' => $this->categoryCollectionFactory,
            'entityManager' => $this->entityManager,
            '_eventManager' => $eventManagerMock,
        ]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function initCategoryCollectionMock()
    {
        $collectionMock = $this->getMockBuilder('Magento\Catalog\Model\ResourceModel\Category\Collection')
            ->disableOriginalConstructor()
            ->getMock();
        $collectionMock->expects($this->any())->method('load')->willReturnSelf();
        $collectionMock->expects($this->any())->method('joinUrlRewrite')->willReturnSelf();
        $collectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $collectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();

        return $collectionMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function initCategoryTreeMock()
    {
        /** @var \Magento\Framework\Data\Tree\Node|\PHPUnit_Framework_MockObject_MockObject $node */
        $node = $this->getMockBuilder('Magento\Framework\Data\Tree\Node')
            ->setMethods(['getIdField', 'loadChildren'])
            ->disableOriginalConstructor()
            ->getMock();
        $node->setData([
            'id'          => 51,
            'level'       => 2,
            'name'        => 'Category Name',
            'description' => 'Lorem ipsum dolor sit amet...',
            'mirakl_sync' => '1'
        ]);
        $node->expects($this->any())->method('getIdField')->willReturn('id');
        $node->expects($this->any())->method('loadChildren')->willReturn([$node]);

        $categoryTreeMock = $this->getMockBuilder('Magento\Catalog\Model\ResourceModel\Category\Tree')
            ->disableOriginalConstructor()
            ->setMethods(['addCollectionData', 'loadNode', 'getNodes'])
            ->getMock();
        $categoryTreeMock->expects($this->any())->method('addCollectionData')->willReturnSelf();
        $categoryTreeMock->expects($this->any())->method('loadNode')->willReturn($node);

        $this->nodeCollection = $this->getMockBuilder('Magento\Framework\Data\Tree\Node\Collection')
            ->setMethods(['count', 'getIterator'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->nodeCollection->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator([$node]));

        $categoryTreeMock->expects($this->any())->method('getNodes')->willReturn($this->nodeCollection);

        return $categoryTreeMock;
    }

    public function testPrepare()
    {
        $this->categoryMock->setData([
            'id'          => 51,
            'name'        => 'Category Name',
            'description' => 'Lorem ipsum dolor sit amet...',
            'mirakl_sync' => '1',
            'store_id'    => '0',
        ]);
        $expected = [
            'category-code'        => $this->categoryMock->getId(),
            'category-label'       => $this->categoryMock->getName(),
            'parent-code'          => '',
            'update-delete'        => 'update',
            'category-description' => 'Lorem ipsum dolor sit amet...',
        ];
        $this->assertEquals($expected, $this->categoryHelper->prepare($this->categoryMock));
        $this->assertEquals($expected, $this->categoryHelper->prepare($this->categoryMock, 'update'));

        $this->categoryMock->setMiraklSync(0);
        $expected = [
            'category-code'        => $this->categoryMock->getId(),
            'category-label'       => $this->categoryMock->getName(),
            'parent-code'          => '',
            'update-delete'        => 'delete',
            'category-description' => 'Lorem ipsum dolor sit amet...',
        ];
        $this->assertEquals($expected, $this->categoryHelper->prepare($this->categoryMock));
        $this->assertEquals($expected, $this->categoryHelper->prepare($this->categoryMock, 'delete'));
    }

    public function testExport()
    {
        $this->api->expects($this->once())
            ->method('export')
            ->willReturn(1234);

        $importId = $this->categoryHelper->export([
            'category-code'  => '51',
            'category-label' => 'Shoes',
            'parent-code'    => '',
            'update-delete'  => 'update',
        ]);

        $this->assertSame(1234, $importId);
    }

    public function testExportAll()
    {
        $process = $this->getMockBuilder('Mirakl\Process\Model\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $this->nodeCollection->expects($this->exactly(3))
            ->method('count')
            ->willReturnOnConsecutiveCalls(0, 1, 2);

        $this->api->expects($this->once())
            ->method('export')
            ->willReturn(1234);

        $this->assertFalse($this->categoryHelper->exportAll($process));
        $this->assertFalse($this->categoryHelper->exportAll($process));
        $this->assertSame(1234, $this->categoryHelper->exportAll($process));
    }

    public function testExportCollection()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection|\PHPUnit_Framework_MockObject_MockObject $collection */
        $collection = $this->categoryCollectionFactory->create();
        $collection->expects($this->exactly(3))
            ->method('count')
            ->willReturnOnConsecutiveCalls(0, 1, 2);
        $this->categoryMock->setData([
            'id'          => 51,
            'name'        => 'Category Name',
            'mirakl_sync' => '1',
            'store_id'    => '0',
        ]);
        $this->categoryMock->expects($this->exactly(2))
            ->method('getLevel')
            ->willReturn(2);
        $collection->expects($this->exactly(2))
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->api->expects($this->any())
            ->method('export')
            ->willReturn(1234);

        $this->assertFalse($this->categoryHelper->exportCollection($collection));
        $this->assertSame(1234, $this->categoryHelper->exportCollection($collection));
        $this->assertSame(1234, $this->categoryHelper->exportCollection($collection, 'update'));
    }
}
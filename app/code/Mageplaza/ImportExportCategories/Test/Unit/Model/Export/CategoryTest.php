<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_ImportExportCategories
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\ImportExportCategories\Test\Unit\Model\Export;

use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory as AttributeColFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryColFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\ImportExportCategories\Model\Export\Category;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class CategoryTest
 * @package Mageplaza\ImportExportCategories\Test\Unit\Model\Export
 */
class CategoryTest extends TestCase
{
    /**
     * @var TimezoneInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeDate;

    /**
     * @var Config|PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var ResourceConnection|PHPUnit_Framework_MockObject_MockObject
     */
    protected $resource;

    /**
     * @var StoreManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var CategoryColFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityColFactory;

    /**
     * @var AttributeColFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeColFactory;

    /**
     * @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var Category|PHPUnit_Framework_MockObject_MockObject
     */
    protected $category;

    protected function setUp()
    {
        $this->localeDate = $this->createMock(Timezone::class);

        $this->config = $this->createPartialMock(Config::class, ['getEntityType']);
        $type         = $this->createMock(Type::class);
        $this->config->expects($this->once())->method('getEntityType')->willReturn($type);

        $this->resource = $this->createMock(ResourceConnection::class);

        $this->storeManager = $this->createMock(StoreManager::class);
        $this->logger       = $this->createMock(Monolog::class);

        $this->entityColFactory    = $this->createMock(CategoryColFactory::class);
        $this->attributeColFactory = $this->createMock(
            AttributeColFactory::class
        );
        $constructorMethods        = [
            'initAttributes',
            '_initStores',
        ];

        $this->category = $this->createPartialMock(
            Category::class,
            $constructorMethods
        );

        foreach ($constructorMethods as $method) {
            $this->category->expects($this->once())->method($method)->will($this->returnSelf());
        }

        $this->category->__construct(
            $this->localeDate,
            $this->config,
            $this->resource,
            $this->storeManager,
            $this->entityColFactory,
            $this->attributeColFactory,
            $this->logger
        );
    }

    /**
     * Test category entity code
     */
    public function testGetEntityTypeCode()
    {
        $this->assertEquals($this->category->getEntityTypeCode(), 'catalog_category');
    }
}

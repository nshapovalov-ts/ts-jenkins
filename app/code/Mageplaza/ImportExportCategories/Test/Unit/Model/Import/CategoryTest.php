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

namespace Mageplaza\ImportExportCategories\Test\Unit\Model\Import;

use Magento\Catalog\Model\Category\Attribute\Source\Mode;
use Magento\Catalog\Model\Category\Attribute\Source\Sortby;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogImportExport\Model\Import\UploaderFactory;
use Magento\Cms\Model\Page\Source\PageLayout;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filter\TranslitUrl;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mageplaza\Core\Helper\AbstractData as HelperData;
use Mageplaza\ImportExportCategories\Model\Import\Category;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class CategoryTest
 * @package Mageplaza\ImportExportCategories\Test\Unit\Model\Import
 */
class CategoryTest extends TestCase
{
    /**
     * @var ProductFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_productFactoryMock;

    /**
     * @var Mode|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_displayModeMock;

    /**
     * @var Sortby|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_sortByMock;

    /**
     * @var UploaderFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_uploaderFactoryMock;

    /**
     * @var Filesystem|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_mediaDirectoryMock;

    /**
     * @var CategoryFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_categoryFactoryMock;

    /**
     * @var PageLayout|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_pageLayoutMock;

    /**
     * @var TranslitUrl|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_tranSlitUrlMock;

    /**
     * @var HelperData|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_helperDataMock;

    /**
     * @var Category
     */
    protected $model;

    protected function setUp()
    {
        $this->_productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->_displayModeMock = $this->getMockBuilder(Mode::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->_sortByMock = $this->getMockBuilder(Sortby::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->_uploaderFactoryMock = $this->getMockBuilder(UploaderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->_mediaDirectoryMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->_categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->_pageLayoutMock = $this->getMockBuilder(PageLayout::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->_tranSlitUrlMock = $this->getMockBuilder(TranslitUrl::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $this->_helperDataMock = $this->getMockBuilder(HelperData::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $helper = new ObjectManager($this);

        $this->model = $helper->getObject(
            Category::class,
            [
                '_productFactory'  => $this->_productFactoryMock,
                '_displayMode'     => $this->_displayModeMock,
                '_sortBy'          => $this->_sortByMock,
                '_uploaderFactory' => $this->_uploaderFactoryMock,
                '_mediaDirectory'  => $this->_mediaDirectoryMock,
                '_categoryFactory' => $this->_categoryFactoryMock,
                '_pageLayout'      => $this->_pageLayoutMock,
                '_tranSlitUrl'     => $this->_tranSlitUrlMock,
                '_helperData'      => $this->_helperDataMock,
            ]
        );
    }

    /**
     * Test generate URL
     * @throws LocalizedException
     */
    public function testGenerateUrlKey()
    {
        $newUrl       = 'new Url';
        $oldUrl       = [
            'new-url1',
            'new-url5',
            'new-url3',
            'new-url4',
            'new-url2',
            'new-url'
        ];
        $expectResult = 'new-url6';
        $actualResult = $this->model->generateUrlKey($newUrl, $oldUrl);
        $this->assertEquals($expectResult, $actualResult);
    }
}

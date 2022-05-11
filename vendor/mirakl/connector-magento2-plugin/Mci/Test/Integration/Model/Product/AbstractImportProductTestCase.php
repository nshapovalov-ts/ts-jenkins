<?php
namespace Mirakl\Mci\Test\Integration\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Mirakl\Core\Test\Integration\TestCase;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Mci\Helper\Data as MciDataHelper;
use Mirakl\Mci\Helper\Product\Import\Finder;
use Mirakl\Mci\Model\Product\Import\Handler\Csv as MciHandler;
use Mirakl\Process\Model\Process as ProcessModel;
use Mirakl\Process\Model\Process;

/**
 * Abstract class for testing product Mci import scenarios
 */
abstract class AbstractImportProductTestCase extends TestCase
{
    /** @var \Magento\Framework\Filesystem */
    protected $fileSystem;

    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    protected $productResource;

    /** @var MciDataHelper */
    protected $mciDatahelper;

    /** @var MciHandler */
    protected $mciImportHandler;

    /** @var CoreHelper */
    protected $coreHelper;

    /** @var ProductResourceFactory */
    protected $productResourceFactory;

    /** @var ProcessModel */
    protected $processModel;

    /** @var MciHandler */
    protected $mciHandler;

    /** @var Finder */
    protected $finder;

    /** @var string[] */
    protected $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileSystem             = $this->objectManager->get(\Magento\Framework\Filesystem::class);
        $this->productResource        = $this->objectManager->create(\Magento\Catalog\Model\ResourceModel\Product::class);
        $this->mciDatahelper          = $this->objectManager->get(MciDataHelper::class);
        $this->mciImportHandler       = $this->objectManager->get(MciHandler::class);
        $this->processModel           = $this->objectManager->create(ProcessModel::class);
        $this->coreHelper             = $this->objectManager->create(CoreHelper::class);
        $this->productResourceFactory = $this->objectManager->create(ProductResourceFactory::class);
        $this->mciHandler             = $this->objectManager->create(MciHandler::class);
        $this->finder                 = $this->objectManager->create(Finder::class);
    }

    /**
     * Create process to run Mci import
     *
     * @param   string  $csv
     * @param   string  $shopId
     * @return  ProcessModel
     */
    protected function createProcess($csv, $shopId)
    {
        $this->processModel = $this->processFactory->create();
        $this->processModel->setType(ProcessModel::TYPE_IMPORT)
            ->setName('TEST MCI products import from path')
            ->setStatus(ProcessModel::STATUS_PENDING)
            ->setHelper(\Mirakl\Mci\Helper\Product\Import::class)
            ->setMethod('runFile')
            ->setFile($this->getFilePath($csv))
            ->setParams([$shopId, $shopId]);

        return $this->processModel;
    }

    /**
     * Execute fixtures
     *
     * @param   string  $csv
     * @return  void
     */
    protected function executeCsv($csv)
    {
        if (!empty($csv)) {
            $this->processModel->setFile($this->getFilePath($csv));
        }
    }

    /**
     * Test product values
     *
     * @param   string  $shopId
     * @param   array   $values
     * @return  Product
     */
    public function validateAllProductValues($shopId, $values)
    {
        $newProduct = $this->finder->findProductByDeduplication($values, Product\Type::TYPE_SIMPLE);
        $this->assertInstanceOf(Product::class, $newProduct);
        $this->assertNotNull($newProduct);

        if (isset($values['ean'])) {
            $this->assertEquals($newProduct->getData('ean'), $values['ean']);
        }

        if (isset($values['shop_skus'])) {
            $this->assertEquals($newProduct->getData(MciDataHelper::ATTRIBUTE_SHOPS_SKUS), $values['shop_skus']);
        }

        if (isset($values['mirakl_sync'])) {
            $this->assertEquals($newProduct->getData('mirakl_sync'), $values['mirakl_sync']);
        }

        $this->assertStringContainsString($shopId, $newProduct->getData(MciDataHelper::ATTRIBUTE_SHOPS_SKUS));
        $this->assertEquals($newProduct->getData('name'), $values['name']);
        $this->assertEquals($newProduct->getData('description'), $values['description']);
        $this->assertEquals($newProduct->getData('color'), $values['color']);
        $this->assertEquals($newProduct->getData('size'), $values['size']);
        $this->assertEquals($newProduct->getStatus(), $values['status']);
        $this->assertEquals($newProduct->getData('mirakl_image_1'), $values['mirakl_image_1'] . '?processed=false');
        $this->assertEquals($newProduct->getData('brand'), $values['brand']);

        return $newProduct;
    }

    /**
     * Run a Mci import
     *
     * @param   string  $shopId
     * @param   string  $csv
     */
    protected function runImport($shopId, $csv)
    {
        $this->createProcess($csv, $shopId);
        $this->processModel->setQuiet(true);
        $this->processModel->run();
    }

    /**
     * Creates a Mirakl process
     *
     * @param   int|null    $limit
     * @return  ProcessModel
     */
    protected function createImageCommandProcess($limit = null)
    {
        $process = $this->processFactory->create();
        $process->setType(ProcessModel::TYPE_CLI)
            ->setName('Products images import')
            ->setStatus(ProcessModel::STATUS_PENDING)
            ->setHelper(\Mirakl\Mci\Helper\Product\Image::class)
            ->setMethod('run');

        if (!empty($limit)) {
            $process->setParams([(int) $limit]);
        }

        return $process;
    }
}

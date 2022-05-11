<?php
namespace Mirakl\Mcm\Test\Integration\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Mirakl\Mcm\Helper\Data as McmDataHelper;
use Mirakl\Mcm\Test\Integration\Model\Product\AbstractImportMcmProductTestCase as MiraklBaseTestCase;

class ImportProductTest extends MiraklBaseTestCase
{
    /**
     * @dataProvider importMcmDataProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_sync/mcm_products/enable_mcm_products 1
     * @magentoConfigFixture current_store mirakl_mcm/import_product/enable_mcm 1
     * @magentoConfigFixture current_store mirakl_mcm/import_product/auto_enable_product 0
     * @magentoConfigFixture current_store mirakl_mcm/import_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mcm/import_product/default_visibility 4
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $csv
     * @param   array   $miraklProductIds
     */
    public function testDataMcmImport($csv, $miraklProductIds)
    {
        $this->runImport($csv);

        $values = [
            'mirakl_category_id' => 3,
            'brand'              => 'Lacoste',
            'name'               => 'Slim Fit Polo',
            'description'        => 'This ...',
            'color'              => '50',
            'size'               => '91',
            'mirakl_image_1'     => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'             => Status::STATUS_DISABLED,
        ];

        $this->validateAllProductValues($miraklProductIds, $values);
    }

    /**
     * @dataProvider importMcmDataProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_sync/mcm_products/enable_mcm_products 1
     * @magentoConfigFixture current_store mirakl_mcm/import_product/enable_mcm 1
     * @magentoConfigFixture current_store mirakl_mcm/import_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mcm/import_product/default_visibility 4
     * @magentoConfigFixture current_store mirakl_mcm/import_product/auto_enable_product 1
     *
     * @param   string  $csv
     * @param   array   $miraklProductIds
     */
    public function testEnableProductMcmImport($csv, $miraklProductIds)
    {
        $this->runImport($csv);

        foreach ($miraklProductIds as $miraklProductId) {
            $newProduct = $this->mcmDatahelper->findSimpleProductByDeduplication($miraklProductId);
            $this->assertInstanceOf(Product::class, $newProduct);
            $this->assertNotNull($newProduct);
            $this->assertEquals($miraklProductId, $newProduct->getData(McmDataHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID));
            $this->assertEquals(Status::STATUS_ENABLED, $newProduct->getStatus());
        }
    }

    /**
     * @dataProvider importUpdateMcmDataProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_sync/mcm_products/enable_mcm_products 1
     * @magentoConfigFixture current_store mirakl_mcm/import_product/enable_mcm 1
     * @magentoConfigFixture current_store mirakl_mcm/import_product/auto_enable_product 0
     * @magentoConfigFixture current_store mirakl_mcm/import_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mcm/import_product/default_visibility 4
     *
     * @param   string  $insertCsv
     * @param   string  $updateCsv
     * @param   string  $miraklProductId
     */
    public function testUpdateProductMcmImport($insertCsv, $updateCsv, $miraklProductId)
    {
        $this->runImport($insertCsv);
        $this->runImport($updateCsv);

        $values = [
            'mirakl_category_id' => 3,
            'brand'              => 'Lacoste Update',
            'name'               => 'Slim Fit Polo UPDATE',
            'description'        => 'This ...UPDATE',
            'color'              => '54',
            'size'               => '167',
            'mirakl_image_1'     => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'             => Status::STATUS_DISABLED,
        ];

        $this->validateAllProductValues([$miraklProductId], $values);
    }

    /**
     * @dataProvider importDeduplicationMcmDataProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/product_attributes.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/single_product.php
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_sync/mcm_products/enable_mcm_products 1
     * @magentoConfigFixture current_store mirakl_mcm/import_product/enable_mcm 1
     * @magentoConfigFixture current_store mirakl_mcm/import_product/auto_enable_product 1
     * @magentoConfigFixture current_store mirakl_mcm/import_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mcm/import_product/default_visibility 4
     *
     * @param   string  $updateCsv
     * @param   string  $miraklProductId
     */
    public function testDeduplicationProductMcmImport($updateCsv, $miraklProductId)
    {
        $this->runImport($updateCsv);

        $newProduct = $this->mcmDatahelper->findSimpleProductByDeduplication($miraklProductId);
        $this->assertInstanceOf(Product::class, $newProduct);
        $this->assertNotNull($newProduct);
        $this->assertEquals($newProduct->getData(McmDataHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID), $miraklProductId);

        $values = [
            'mirakl_category_id' => 3,
            'brand'              => 'Lacoste Update',
            'name'               => 'Slim Fit Polo UPDATE',
            'description'        => 'This ...UPDATE',
            'color'              => '54',
            'size'               => '167',
            'mirakl_image_1'     => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'             => Status::STATUS_ENABLED,
        ];

        // Check that visibility and tax class id did not change after creation.
        $this->assertEquals(0, $newProduct->getTaxClassId());
        $this->assertEquals(Product\Visibility::VISIBILITY_BOTH, $newProduct->getVisibility());

        $this->validateAllProductValues([$miraklProductId], $values);
    }

    /**
     * @return  array
     */
    public function importDeduplicationMcmDataProvider()
    {
        return [
            ['CM51_single_product_update.csv', 'abc5-4cf1-acdb-56152a77bc56'],
        ];
    }

    /**
     * @return  array
     */
    public function importUpdateMcmDataProvider()
    {
        return [
            ['CM51_single_product.csv', 'CM51_single_product_update.csv', 'abc5-4cf1-acdb-56152a77bc56'],
        ];
    }

    /**
     * @return  array
     */
    public function importMcmDataProvider()
    {
        return [
            ['CM51_single_product2.csv', ['abc5-4cf1-acdb-56152a77bc65']],
        ];
    }

    /**
     * @return  array
     */
    public function importMcmDataValidationErrorProvider()
    {
        return [
            ['CM51_validation_error.csv', ['abc5-4cf1-acdb-56152a77bc56'], ['Error']],
        ];
    }
}

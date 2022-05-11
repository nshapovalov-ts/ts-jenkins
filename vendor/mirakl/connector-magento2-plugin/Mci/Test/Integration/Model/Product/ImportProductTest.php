<?php
namespace Mirakl\Mci\Test\Integration\Model\Product;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Mirakl\Mci\Test\Integration\Model\Product\AbstractImportProductTestCase as MiraklBaseTestCase;

class ImportProductTest extends MiraklBaseTestCase
{
    /**
     * @dataProvider importMciDataProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_enable_product 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_visibility 4
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $csv
     */
    public function testDataMciImport($csv)
    {
        $this->runImport('2010', $csv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'ean'            => 'EAN1234',
            'size'           => '91',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_DISABLED,
        ];

        $this->validateAllProductValues('2010', $values);

        $this->assertStringNotContainsString('Creating process for P43 import report...', $this->processModel->getOutput());
    }

    /**
     * @dataProvider importMciDataProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_enable_product 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_visibility 4
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $csv
     */
    public function testDataMciDisabledProductImport($csv)
    {
        $this->runImport('2010', $csv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
        ];

        $this->validateAllProductValues('2010', $values);
    }

    /**
     * @dataProvider importMciDataProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_enable_product 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_visibility 4
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $csv
     */
    public function testDataMciEnableReportImport($csv)
    {
        $this->runImport('2010', $csv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
        ];

        $this->validateAllProductValues('2010', $values);

        $this->assertStringContainsString('Creating process for P43 import report...', $this->processModel->getOutput());
    }

    /**
     * @dataProvider importMciDataDeduplicationProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_enable_product 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_visibility 4
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/deduplication_attributes ean
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $productCreationCsv
     * @param   string  $productUpdateCsv
     */
    public function testDataNoUpdateImport($productCreationCsv, $productUpdateCsv)
    {
        $this->runImport('2010', $productCreationCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
        ];

        $this->validateAllProductValues('2010', $values);

        $this->runImport('2010', $productUpdateCsv);

        $this->validateAllProductValues('2010', $values);
    }

    /**
     * @dataProvider importMciDataDeduplicationProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_enable_product 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_visibility 4
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/deduplication_attributes ean
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/update_existing_products 1
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $productCreationCsv
     * @param   string  $productUpdateCsv
     */
    public function testDataDeduplicationImport($productCreationCsv, $productUpdateCsv)
    {
        $this->runImport('2010', $productCreationCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
            'shop_skus'      => '2010|SHOPSKU',
        ];

        $this->validateAllProductValues('2010', $values);

        $this->runImport('2010', $productUpdateCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste UPDATE',
            'name'           => 'Slim Fit Polo UPDATE',
            'description'    => 'This ... UPDATE',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
        ];

        $this->validateAllProductValues('2010', $values);
    }

    /**
     * @dataProvider importMciDataDeduplicationProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_enable_product 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_visibility 4
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/deduplication_attributes ean
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/update_existing_products 1
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $productCreationCsv
     * @param   string  $productUpdateCsv
     */
    public function testDataAnotherShopDeduplicationImport($productCreationCsv, $productUpdateCsv)
    {
        $this->runImport('2010', $productCreationCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
        ];

        $this->validateAllProductValues('2010', $values);

        $this->runImport('2000', $productUpdateCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste UPDATE',
            'name'           => 'Slim Fit Polo UPDATE',
            'description'    => 'This ... UPDATE',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
            'shop_skus'      => '2010|SHOPSKU,2000|SHOPSKU',
        ];

        $this->validateAllProductValues('2000', $values);
    }

    /**
     * @dataProvider importMciDataDeduplicationProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_enable_product 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_visibility 4
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/deduplication_attributes ean
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/update_existing_products 0
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $productCreationCsv
     * @param   string  $productUpdateCsv
     */
    public function testDataAnotherShopNoUpdateImport($productCreationCsv, $productUpdateCsv)
    {
        $this->runImport('2010', $productCreationCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
            'shop_skus'      => '2010|SHOPSKU',
        ];

        $this->validateAllProductValues('2010', $values);

        $this->runImport('2000', $productUpdateCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
            'shop_skus'      => '2010|SHOPSKU,2000|SHOPSKU',
        ];

        $this->validateAllProductValues('2000', $values);
    }

    /**
     * @dataProvider importMciDataDeduplicationProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_enable_product 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_visibility 4
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/deduplication_attributes ean
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/update_existing_products 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_flag_product_sync 1
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $productCreationCsv
     * @param   string  $productUpdateCsv
     */
    public function testDataMiraklSync($productCreationCsv, $productUpdateCsv)
    {
        $this->runImport('2010', $productCreationCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
            'shop_skus'      => '2010|SHOPSKU',
            'mirakl_sync'    => '1',
            'tax_class_id'   => 2,
            'visibility'     => 4,
        ];

        $this->validateAllProductValues('2010', $values);

        $this->runImport('2000', $productUpdateCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
            'shop_skus'      => '2010|SHOPSKU,2000|SHOPSKU',
            'mirakl_sync'    => '1',
            'tax_class_id'   => 2,
            'visibility'     => 4,
        ];

        $this->validateAllProductValues('2000', $values);
    }

    /**
     * @dataProvider importMciDataProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_enable_product 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_visibility 4
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/deduplication_attributes ean
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/update_existing_products 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_flag_product_sync 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/check_data_hash 1
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $productCreationCsv
     */
    public function testDataEnableHash($productCreationCsv)
    {
        $this->runImport('2010', $productCreationCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
            'shop_skus'      => '2010|SHOPSKU',
            'mirakl_sync'    => '1',
            'tax_class_id'   => 2,
            'visibility'     => 4,
        ];

        $this->validateAllProductValues('2010', $values);

        $this->runImport('2010', $productCreationCsv);

        $this->assertStringContainsString('Skipping row 2 because already imported (SHOPSKU)', $this->processModel->getOutput());
    }

    /**
     * @dataProvider importMciDataDeduplicationMultivaluesProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 0
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_enable_product 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/default_visibility 4
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/deduplication_attributes ean
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/update_existing_products 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/auto_flag_product_sync 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/enable_deduplication_multivalues 1
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $productCreationCsv
     * @param   string  $productUpdateCsv
     */
    public function testDataMultiValues($productCreationCsv, $productUpdateCsv)
    {
        $this->runImport('2010', $productCreationCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste',
            'name'           => 'Slim Fit Polo',
            'description'    => 'This ...',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234,EAN12345',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
            'shop_skus'      => '2010|SHOPSKU',
        ];

        $this->validateAllProductValues('2010', $values);

        $this->runImport('2000', $productUpdateCsv);

        $values = [
            'category'       => 3,
            'brand'          => 'Lacoste UPDATE',
            'name'           => 'Slim Fit Polo UPDATE',
            'description'    => 'This ... UPDATE',
            'color'          => '50',
            'size'           => '91',
            'ean'            => 'EAN1234,EAN12345',
            'mirakl_image_1' => 'https://assets.probikeshop.fr/images/products2/266/127251/127251-370-520-main.jpg',
            'status'         => Status::STATUS_ENABLED,
            'shop_skus'      => '2010|SHOPSKU,2000|SHOPSKU',
        ];

        $this->validateAllProductValues('2000', $values);
    }

    /**
     * @return  array
     */
    public function importMciDataProvider()
    {
        return [
            ['single_product.csv'],
        ];
    }

    /**
     * @return  array
     */
    public function importMciDataDeduplicationProvider()
    {
        return [
            ['single_product.csv', 'single_product_update.csv'],
        ];
    }

    /**
     * @return  array
     */
    public function importMciDataDeduplicationMultivaluesProvider()
    {
        return [
            ['single_product_multivalues.csv', 'single_product_update.csv'],
        ];
    }
}

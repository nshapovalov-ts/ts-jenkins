<?php
namespace Mirakl\Mci\Test\Integration\Model\Product;

use Mirakl\Mci\Test\Integration\Model\Product\AbstractImportProductTestCase as MiraklBaseTestCase;

class FailImportProductTest extends MiraklBaseTestCase
{
    /**
     * @dataProvider importMciAttributeSetErrorDataProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set_rollback.php
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 0
     *
     * @param   string  $csv
     * @param   array   $errors
     */
    public function testDataAttributeSetErrorMciImport($csv, $errors)
    {
        $this->runImport('2010', $csv);

        foreach ($errors as $error) {
            $this->assertStringContainsString($error, $this->processModel->getOutput());
        }
    }

    /**
     * @dataProvider importMciErrorDataProvider
     *
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mci/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_mci/import_shop_product/send_import_report 0
     *
     * @param   string  $csv
     * @param   array   $errors
     */
    public function testDataErrorMciImport($csv, $errors)
    {
        try {
            $this->runImport('2010', $csv);
        } catch (\Exception $e) {
            // Do not stop test on exception
        }

        foreach ($errors as $error) {
            $this->assertStringContainsString($error, $this->processModel->getOutput());
        }
    }

    /**
     * @return  array
     */
    public function importMciErrorDataProvider()
    {
        return [
            ['single_product_with_category_not_found.csv', ['Could not find category with id "1024"']],
            ['single_product_without_shop_sku.csv', ['Could not find "shop_sku" column in product data']],
            ['single_product_with_empty_shop_sku.csv', ['Column "shop_sku" cannot be empty']],
            ['empty.csv', ['No valid delimiter found.']],
            ['single_product_without_category.csv', ['Undefined index: category']],
        ];
    }

    /**
     * @return  array
     */
    public function importMciAttributeSetErrorDataProvider()
    {
        return [
            ['single_product_invalid_shop_sku.csv', ['Could not find attribute set for category "3"']],
        ];
    }
}

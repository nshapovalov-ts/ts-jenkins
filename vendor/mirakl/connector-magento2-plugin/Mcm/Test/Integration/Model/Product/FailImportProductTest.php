<?php
namespace Mirakl\Mcm\Test\Integration\Model\Product;

use Mirakl\Mcm\Test\Integration\Model\Product\AbstractImportMcmProductTestCase as MiraklBaseTestCase;

/**
 * @magentoDbIsolation  enabled
 * @magentoAppIsolation disabled
 */
class FailImportProductTest extends MiraklBaseTestCase
{
    /**
     * @dataProvider importMcmDataFailProvider
     *
     * @magentoConfigFixture current_store mirakl_mcm/import_product/enable_mcm 0
     *
     * @param   string  $csv
     * @param   array   $errors
     */
    public function testDataDisabledMcm($csv, $errors)
    {
        $this->testDataErrorMcmImport($csv, $errors);
    }

    /**
     * @dataProvider importMcmErrorDataProvider
     *
     * @magentoConfigFixture current_store mirakl_mcm/import_product/enable_mcm 1
     *
     * @param   string  $csv
     * @param   array   $errors
     */
    public function testDataErrorMcmImport($csv, $errors)
    {
        $this->runImport($csv);

        foreach ($errors as $error) {
            $this->assertStringContainsString($error, $this->processModel->getOutput());
        }
    }

    /**
     * @return  array
     */
    public function importMcmErrorDataProvider()
    {
        return [
            ['CM51_single_product_category_not_found.csv', ['Could not find category with id "POLO"']],
            ['CM51_single_product_attribute_set_not_found.csv', ['Could not find attribute set for category "3"']],
            ['CM51_empty.csv', ['Importing MCM file...']],
            ['CM51_without_mirakl_product_id.csv', ['Could not find "mirakl-product-id" column in product data']],
            ['CM51_without_mirakl_product_id_value.csv', ['Column "mirakl-product-id" cannot be empty']],
            ['CM51_without_category.csv', ['Could not find "category" column in product data']],
        ];
    }

    /**
     * @return  array
     */
    public function importMcmDataFailProvider()
    {
        return [
            ['CM51_single_product_category_not_found.csv', ['Module MCM is disabled. See your Mirakl MCM configuration']],
        ];
    }
}
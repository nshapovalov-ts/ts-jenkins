<?php
namespace Mirakl\Mcm\Test\Integration\Model\Product;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Mirakl\Mcm\Test\Integration\Model\Product\AbstractImportMcmProductTestCase as MiraklBaseTestCase;
use Mirakl\Mcm\Helper\Data as McmDataHelper;

class ImportVariantProductTest extends MiraklBaseTestCase
{
    /**
     * @dataProvider importVariantMcmDataProvider
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
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/attributes_variant.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/product_attributes.php

     * @param   string  $csv
     * @param   array   $miraklProductIds
     * @param   string  $variantCode
     */
    public function testVariantProductMcmImport($csv, $miraklProductIds, $variantCode)
    {
        $this->runImport($csv);

        foreach ($miraklProductIds as $miraklProductId) {
            $newProduct = $this->mcmDatahelper->findSimpleProductByDeduplication($miraklProductId);
            $this->assertInstanceOf(\Magento\Catalog\Model\Product::class, $newProduct);
            $this->assertNotNull($newProduct);
            $this->assertEquals($newProduct->getData(McmDataHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID), $miraklProductId);
            $this->assertEquals($newProduct->getStatus(), Status::STATUS_DISABLED);

            // Test parent creation
            $parentProduct = $this->coreHelper->getParentProduct($newProduct);
            $this->assertNotNull($parentProduct);
            $this->productResource->load($parentProduct, $parentProduct->getId());
            $this->assertEquals($parentProduct->getData(McmDataHelper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE), $variantCode);
        }
    }

    /**
     * @dataProvider importCreateParentVariantMcmDataProvider
     *
     * @magentoDbIsolation disabled
     *
     * @magentoConfigFixture current_store mirakl_api/general/enable 1
     * @magentoConfigFixture current_store mirakl_sync/mcm_products/enable_mcm_products 1
     * @magentoConfigFixture current_store mirakl_mcm/import_product/enable_mcm 1
     * @magentoConfigFixture current_store mirakl_mcm/import_product/auto_enable_product 0
     * @magentoConfigFixture current_store mirakl_mcm/import_product/default_tax_class 2
     * @magentoConfigFixture current_store mirakl_mcm/import_product/default_visibility 4
     *
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/attributes_variant.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/product_attributes.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/single_mcm_product.php
     *
     * @param   string  $singleProductFile
     * @param   string  $variantProductFile
     * @param   string  $miraklProductId
     * @param   string  $variantCode
     */
    public function testCreateParentVariantProductMcmImport($singleProductFile, $variantProductFile, $miraklProductId, $variantCode)
    {
        // Import #1
        $this->runImport($singleProductFile);

        $newProduct = $this->mcmDatahelper->findSimpleProductByDeduplication($miraklProductId);
        $this->assertNotNull($newProduct);
        $this->assertInstanceOf(\Magento\Catalog\Model\Product::class, $newProduct);
        $this->assertEquals($newProduct->getData(McmDataHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID), $miraklProductId);

        // Import #2
        $this->runImport($variantProductFile);
        $newProduct = $this->mcmDatahelper->findSimpleProductByDeduplication($miraklProductId);
        $this->assertNotNull($newProduct);
        $this->assertInstanceOf(\Magento\Catalog\Model\Product::class, $newProduct);
        $this->assertEquals($newProduct->getData(McmDataHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID), $miraklProductId);
        $this->assertEquals($newProduct->getData('name'), 'Slim Fit Polo UPDATE');

        // Test parent creation
        $parentProduct = $this->coreHelper->getParentProduct($newProduct);
        $this->assertNotNull($parentProduct);
        $this->productResourceFactory->create()->load($parentProduct, $parentProduct->getId());
        $this->assertEquals($parentProduct->getData(McmDataHelper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE), $variantCode);
    }

    /**
     * @dataProvider importUpdateVariantAlreadyPresentMcmDataProvider
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
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/attributes_variant.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/categories_attribute_set.php
     * @magentoDataFixture ../../../../vendor/mirakl/connector-magento2-plugin/Mcm/Test/Integration/Model/Product/_fixtures/product_attributes.php
     *
     * @param   string  $updateVariantProductFile
     */
    public function testUpdateAlreadyVariantProductMcmImport($updateVariantProductFile)
    {
        $this->runImport($updateVariantProductFile);
        $this->assertStringContainsString('A variant product already exists with the same variants as provided data', $this->processModel->getOutput());
    }

    /**
     * @return  array
     */
    public function importUpdateVariantAlreadyPresentMcmDataProvider()
    {
        return [
            ['CM51_update_single_product_variant_already_present.csv'],
        ];
    }

    /**
     * @return  array
     */
    public function importVariantMcmDataProvider()
    {
        return [
            ['CM51_multi_variant_product.csv', ['abc5-4cf1-acdb-56152a77bc56', 'abc4-5cf1-acdb-56152a77bc56'], 'variant_code'],
        ];
    }

    /**
     * @return  array
     */
    public function importCreateParentVariantMcmDataProvider()
    {
        return [
            ['CM51_single_product.csv', 'CM51_create_parent_variant_product.csv', 'abc5-4cf1-acdb-56152a77bc56', 'variant_code'],
        ];
    }
}
<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Model\Synchronizer;

use Mirakl\Process\Model\Process;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Retailplace\MiraklShop\Model\ResourceModel\Shop;
use Mirakl\MMP\Common\Domain\AdditionalField\AdditionalFieldType;
use Mirakl\MMP\FrontOperator\Domain\Collection\Shop\ShopCollection;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Framework\Serialize\Serializer\Serialize;

/**
 * Class ShopUpdater
 */
class ShopUpdater
{
    /** @var string */
    public const ARRAY_SEPARATOR = ',';

    /** @var string */
    public const NETWORK_FIELD = 'network';
    public const AGHA_FIELD_VALUE = 'AGHA';
    public const AU_POST_FIELD_VALUE = 'AU_Post';
    public const FIXED_PERCENT_SHIPPING_FIELD_CODE = 'shipping';
    public const CUSTOM_TAGS = 'custom-tags';
    public const LASTDATE = 'lastdate';
    public const OPEN_DURING_XMAS_VALUE = 'openduringXmas';
    public const SLOWER_THAN_AVERAGE_VALUE = 'slowerthanaverage';
    public const MIRAKL_SHOP_IDS_ATTRIBUTE = 'mirakl_shop_ids';

    /** @var \Magento\Catalog\Model\Product\Attribute\Repository */
    private $attributeRepository;

    /** @var array */
    private $tableFields;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Magento\Framework\Serialize\Serializer\Serialize */
    private $serializer;

    /**
     * Constructor
     *
     * @param \Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        AttributeRepository $attributeRepository,
        ResourceConnection $resourceConnection,
        Serialize $serializer
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->resourceConnection = $resourceConnection;
        $this->serializer = $serializer;
    }

    /**
     * Sync Shops
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function synchronize(ShopCollection $shops, Process $process, $chunkSize = 100): int
    {
        if (!$shops->count()) {
            throw new \Exception(__('Shops to synchronize cannot be empty.'));
        }

        /** Load existing mirakl_shop_ids EAV attribute */
        $attribute = $this->attributeRepository->get(self::MIRAKL_SHOP_IDS_ATTRIBUTE);
        if (!$attribute) {
            throw new \Exception(__('mirakl_shop_ids attribute is not created.'));
        }

       $this->chunkSize = $chunkSize;

        /** Load existing EAV option ids associated to shop ids */
        $customShops = $this->getEavOptionIds();

        $eavShopOptions = [];
        foreach ($attribute->getOptions() as $option) {
            /** @var \Magento\Eav\Api\Data\AttributeOptionInterface $option */
            if ($option->getValue()) {
                $eavShopOptions[$option->getValue()] = $option;
            }
        }

        $insert = [];
        foreach ($shops->toArray() as $shop) {
            /** Check if EAV option exists */
            if (isset($customShops[$shop['id']]) &&
                isset($eavShopOptions[$customShops[$shop['id']]])) {
                $optionId = $customShops[$shop['id']];
                /** Update EAV option if label has changed */
                if ($eavShopOptions[$optionId]->getLabel() != $shop['name']) {
                    $this->getConnection()->update(
                        $this->getTableName('eav_attribute_option_value'),
                        ['value' => $shop['name']],
                        ['option_id = ?' => $optionId, 'store_id = ?' => 0]
                    );
                }
            } else {
                /** Create EAV option */
                $optionTable = $this->getTableName('eav_attribute_option');
                $optionValueTable = $this->getTableName('eav_attribute_option_value');

                $eavAttributeOptionData = ['attribute_id' => $attribute->getId()];
                $this->getConnection()->insert($optionTable, $eavAttributeOptionData);
                $optionId = $this->getConnection()->lastInsertId($optionTable);

                $eavAttributeOptionValueData = ['option_id' => $optionId, 'store_id' => 0, 'value' => $shop['name']];
                $this->getConnection()->insert($optionValueTable, $eavAttributeOptionValueData);
            }

            $data = $this->processFields($shop);
            $data[ShopInterface::FREE_SHIPPING] = $shop['shipping_info']['free_shipping'];
            $data[ShopInterface::EAV_OPTION_ID] = $optionId;
            $data[ShopInterface::ADDITIONAL_INFO] = $this->serializer->serialize($shop);
            $insert[$data[ShopInterface::ID]] = $data;
            $process->output(__('Saving shop %1', $data['id']));
        }

        $affected = $this->bulkInsert($insert, $chunkSize);

        return $affected;
    }

    /**
     * Keep existed logic for update shop with by SQL insert
     *
     * @param array $insert
     * @param int $affected
     *
     * @return int
     */
    public function bulkInsert(array $insert, int $affected = 0): int
    {
        $adapter = $this->getConnection();
        foreach (array_chunk($insert, $this->chunkSize) as $shopsData) {
            $affected += $adapter->insertOnDuplicate(
                $this->getTableName(Shop::TABLE_NAME),
                $shopsData,
                $this->getTableFields()
            );
        }
        return $affected;
    }

    /**
     * Get Adapter
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * Get list of EAV Option IDs for Shops
     *
     * @return array
     */
    private function getEavOptionIds(): array
    {
        $select = $this->getConnection()->select()
            ->from($this->getTableName(Shop::TABLE_NAME), ['id', 'eav_option_id']);

        return $this->getConnection()->fetchPairs($select);
    }

    /**
     * Get Table Name
     *
     * @param string $tableName
     * @return string
     */
    private function getTableName(string $tableName): string
    {
        return $this->resourceConnection->getConnection()->getTableName($tableName);
    }

    /**
     * Process Shop Data
     *
     * @param array $shop
     * @return array
     */
    private function processFields(array $shop): array
    {
        $shop[ShopInterface::AGHA_SELLER] = 0;
        $shop[ShopInterface::AU_POST_SELLER] = 0;
        $shop[ShopInterface::IS_FIXED_PERCENT_SHIPPING] = 0;

        if (isset($shop['additional_field_values']) && is_array($shop['additional_field_values'])) {
            foreach ($shop['additional_field_values'] as $column) {
                if (isset($column['code']) && isset($column['value'])) {
                    $shop = $this->processColumnValue($column, $shop);
                }
            }
        }

        $data = [];
        foreach ($this->getTableFields() as $field) {
            $data[$field] = $shop[$field] ?? null;
        }

        return $data;
    }

    /**
     * Process Column Data
     *
     * @param array $column
     * @param array $shop
     * @return array
     */
    private function processColumnValue(array $column, array $shop): array
    {
        $value = $column['value'];
        $code = $column['code'];

        if ($code == self::NETWORK_FIELD) {
            if (!is_array($value)) {
                $value = [$value];
            }
            $shop[ShopInterface::AGHA_SELLER] = in_array(self::AGHA_FIELD_VALUE, $value);
            $shop[ShopInterface::AU_POST_SELLER] = in_array(self::AU_POST_FIELD_VALUE, $value);
        } elseif ($code == self::FIXED_PERCENT_SHIPPING_FIELD_CODE) {
            $shop[ShopInterface::IS_FIXED_PERCENT_SHIPPING] = $value === 'true';
        } elseif ($code == self::CUSTOM_TAGS) {
            if (is_array($value)) {
                foreach ($value as $valueItem) {
                    if ($valueItem == self::OPEN_DURING_XMAS_VALUE) {
                        $shop[ShopInterface::OPEN_DURING_XMAS] = 1;
                    }
                }
            }
        } else {
            switch ($column['type']) {
                case AdditionalFieldType::STRING:
                case AdditionalFieldType::DATE:
                case AdditionalFieldType::LINK:
                case AdditionalFieldType::REGEX:
                case AdditionalFieldType::TEXTAREA:
                    $value = (string) $column['value'];
                    break;
                case AdditionalFieldType::BOOLEAN:
                    $value = $value === 'true';
                    break;
                case AdditionalFieldType::NUMERIC:
                    $value = (float) $value;
                    break;
                case AdditionalFieldType::TYPE_LIST:
                case AdditionalFieldType::MULTI_VALUES:
                    $value = is_array($value) ? implode(self::ARRAY_SEPARATOR, $value) : $value;
                    break;
            }

            $shop[$code] = $value;
            $shop[$this->cleanDataKey($code)] = $value;
        }

        return $shop;
    }

    /**
     * Change Mirakl Field Code format to Magento DB format
     *
     * @param string $col
     * @return string
     */
    private function cleanDataKey(string $col): string
    {
        return str_replace('-', '_', $col);
    }

    /**
     * Get Table Fields
     *
     * @return array
     */
    private function getTableFields(): array
    {
        if (!$this->tableFields) {
            $tableFields = $this->getConnection()->describeTable($this->getTableName(Shop::TABLE_NAME));
            $this->tableFields = array_diff(array_keys($tableFields), $this->getExcludedFields());
        }

        return $this->tableFields;
    }

    /**
     * Get fields excluded from update from Mirakl
     *
     * @return array
     */
    private function getExcludedFields(): array
    {
        return [
            ShopInterface::HAS_NEW_PRODUCTS,
        ];
    }
}

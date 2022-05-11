<?php declare(strict_types=1);

namespace Retailplace\MiraklSeller\Plugin;

use Magento\Framework\App\ResourceConnection;
use Mirakl\Connector\Model\Offer\ImportFileBuilder;

class OfferImportFileBuilder
{
    const CLEARANCE_FIELD_CODE = 'clearance';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * ImportFileBuilder constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param ImportFileBuilder $subject
     * @param array $result
     * @return array
     */
    public function afterBuildData(
        ImportFileBuilder $subject,
        array $result
    ) {
        /*
         * Mview xml issue wrong entity_column
         * Need to use entity_id for entity_column
         * Getting product entity id
         */
        if (!empty($result)) {
            $productsSku = array_column($result, "product_sku");
            $connection = $this->resourceConnection->getConnection();
            $catalogProductTable = $connection->getTableName('catalog_product_entity');
            $select = $connection->select()->from(
                $catalogProductTable,
                ['sku', 'entity_id']
            )->where("sku IN (?)", $productsSku);

            $data = $connection->fetchPairs($select);

            array_walk(
                $result,
                function (&$item, $index, $data) {
                    if (isset($data[$item['product_sku']])) {
                        $item['entity_id'] = $data[$item['product_sku']];
                    }
                    /**
                     * Added custom clearance field to optimize custom sale page
                     */
                    $item[self::CLEARANCE_FIELD_CODE] = isset($item[self::CLEARANCE_FIELD_CODE]) && $item[self::CLEARANCE_FIELD_CODE] == "true" ? 1 : 0;
                },
                $data
            );
        }
        return $result;
    }
}

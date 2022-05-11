<?php
namespace Mirakl\Connector\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Mirakl\Api\Helper\Offer as OfferApiHelper;
use Mirakl\Connector\Model\Offer\ImportFileBuilder;
use Mirakl\MMP\Common\Domain\Discount;
use Mirakl\MMP\FrontOperator\Domain\Offer as SdkOffer;
use Psr\Log\LoggerInterface;

class Offer extends AbstractDb
{
    /**
     * @var ImportFileBuilder
     */
    protected $importFileBuilder;

    /**
     * @var OfferApiHelper
     */
    protected $offerApiHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var int
     */
    protected $offersImportChunkSize;

    /**
     * @param   Context             $context
     * @param   ImportFileBuilder   $importFileBuilder
     * @param   OfferApiHelper      $offerApiHelper
     * @param   LoggerInterface     $logger
     * @param   string|null         $connectionName
     * @param   int                 $offersImportChunkSize
     */
    public function __construct(
        Context $context,
        ImportFileBuilder $importFileBuilder,
        OfferApiHelper $offerApiHelper,
        LoggerInterface $logger,
        $connectionName = null,
        $offersImportChunkSize = 500
    ) {
        parent::__construct($context, $connectionName);
        $this->importFileBuilder = $importFileBuilder;
        $this->offerApiHelper = $offerApiHelper;
        $this->logger = $logger;
        $this->offersImportChunkSize = $offersImportChunkSize;
    }

    /**
     * Initialize main table and table id field
     *
     * @return  void
     */
    protected function _construct()
    {
        $this->_init('mirakl_offer', 'offer_id');
    }

    /**
     * Clears deleted offers and returns the number of removed offers
     *
     * @return  int
     */
    public function clearDeletedOffers()
    {
        return $this->getConnection()->delete($this->getMainTable(), ['deleted = ?' => 'true']);
    }

    /**
     * Returns default values of mirakl_offer table to not block offers import if some columns are empty in API OF51
     *
     * @return  array
     */
    public function getDefaultValues()
    {
        $defaultValues = [];
        $tableColumns = $this->getConnection()->describeTable($this->getMainTable());

        foreach ($tableColumns as $key => $info) {
            switch ($info['DATA_TYPE']) {
                case 'int':
                case 'decimal':
                    $defaultValues[$key] = 0;
                    break;
                case 'datetime':
                    $defaultValues[$key] = '0000-00-00';
            }
        }

        return $defaultValues;
    }

    /**
     * Save all offers into mirakl_offer table with the very fast LOAD DATA INFILE.
     * The LOCAL option allows the file to be downloaded by the MySQL server
     * => needs the option PDO::MYSQL_ATTR_LOCAL_INFILE defined to true in app/etc/env.php (driver_options)
     * Existing rows (based on offer id) will be overwritten thanks to the REPLACE option.
     * Also ignore first line that contains column names.
     *
     * @param   string  $file
     * @param   bool    $useDatabaseImport
     * @return  int
     */
    public function importFile($file, $useDatabaseImport = true)
    {
        $tableName    = $this->getMainTable();
        $tableColumns = array_keys($this->getConnection()->describeTable($tableName));
        $data = $this->importFileBuilder->buildData($file, $tableColumns, $this->getDefaultValues());
        $count = count($data);

        if ($useDatabaseImport) {
            $file = $this->importFileBuilder->buildFile($data);

            $loadDataSql = <<<SQL
LOAD DATA LOCAL INFILE '$file' REPLACE
INTO TABLE `$tableName`
CHARACTER SET UTF8
FIELDS TERMINATED BY ';' ENCLOSED BY '"' ESCAPED BY ''
IGNORE 1 LINES;
SQL;

            try {
                $this->getConnection()->query($loadDataSql);

                return $count;
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
                // Log exception message and try a regular PHP import
            }
        }

        foreach (array_chunk($data, $this->offersImportChunkSize) as $offers) {
            $this->getConnection()->insertOnDuplicate($tableName, $offers);
        }

        return $count;
    }

    /**
     * Updates single offer prices
     *
     * @param   int         $offerId
     * @param   float       $price
     * @param   Discount    $discount
     * @return  int
     */
    public function updateOfferPrices($offerId, $price, Discount $discount = null)
    {
        $bind = ['price' => $price];

        if ($discount) {
            $bind['origin_price'] = (float) $discount->getOriginPrice();
            $bind['discount_price'] = (float) $discount->getDiscountPrice();
            $bind['discount_ranges'] = (string) $discount->getRanges();
        }

        return $this->getConnection()
            ->update($this->getMainTable(), $bind, ['offer_id = ?' => $offerId]);
    }

    /**
     * @param   int $offerId
     * @param   int $qty
     * @return  int
     */
    public function updateOfferQty($offerId, $qty)
    {
        return $this->getConnection()
            ->update($this->getMainTable(), ['quantity' => $qty], ['offer_id = ?' => $offerId]);
    }

    /**
     * @param   int $offerId
     * @return  SdkOffer
     */
    public function updateOrderConditions($offerId)
    {
        $offer = $this->offerApiHelper->getOffer($offerId);

        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'quantity'           => $offer->getQuantity(),
                'min_order_quantity' => $offer->getMinOrderQuantity(),
                'max_order_quantity' => $offer->getMaxOrderQuantity(),
                'package_quantity'   => $offer->getPackageQuantity(),
            ],
            ['offer_id = ?' => $offerId]
        );

        return $offer;
    }
}

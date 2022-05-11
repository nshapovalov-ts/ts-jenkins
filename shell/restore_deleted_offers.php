<?php
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

use Magento\Framework\App\Bootstrap;
use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\ImportMode;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportRequest;

require 'app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
/** @var \Magento\Framework\App\ResourceConnection $resource */
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();
$offerTable = $connection->getTableName('mirakl_offer_copy');

/** @var \MiraklSeller\Api\Helper\Offer $offerApi */
$offerApi = $objectManager->get('MiraklSeller\Api\Helper\Offer');

$miraklConnectionFactory = $objectManager->get('MiraklSeller\Api\Model\ConnectionFactory');
/** @var \MiraklSeller\Api\Model\Connection $miraklConnection */
$miraklConnection = $miraklConnectionFactory->create();
$miraklConnection->load(2);

/** @var \MiraklSeller\Api\Model\Log\RequestLogValidator $requestLogValidator */
$requestLogValidator = $objectManager->get('MiraklSeller\Api\Model\Log\RequestLogValidator');

/** @var \MiraklSeller\Api\Model\Log\LoggerManager $loggerManager */
$loggerManager = $objectManager->get('MiraklSeller\Api\Model\Log\LoggerManager');

$deletedOffersPath = implode(DIRECTORY_SEPARATOR, [BP, 'var', 'mirakl', 'Deleted_offers.csv']);
$file = fopen($deletedOffersPath, "r");
$shopSkus = [];
while (($data = fgetcsv($file, 0, ";")) !== false) {
    if (isset($data[1]) && is_numeric($data[1]) && isset($data[2]) && is_numeric($data[2])) {
        $shopId = $data[1];
        $offerId = $data[2];
        $shopSku = $data[3];
        $shopSkus[$shopId][$offerId] = $shopSku;
    }
}

$shops = [];
foreach ($shopSkus as $shopId => $shopSkusByOfferIds) {
    //if ($shopId != 2218) {
    //    continue;
    //}
    $shops[$shopId] = [
        'id'              => $shopId,
        'affected_offers' => count($shopSkusByOfferIds),
        'shop_name'       => '',
        'fixed_offers'    => 0,
    ];

    $select = $connection->select()
        ->from(['offer' => $offerTable], '*')
        ->where('offer_id in (?)', array_keys($shopSkusByOfferIds));

    $offers = $connection->fetchAll($select);

    $data = [];
    foreach ($offers as $offer) {
        if ($shops[$shopId]['shop_name'] == '') {
            $shops[$shopId]['shop_name'] = $offer['shop_name'];
            $shops[$shopId]['fixed_offers'] = count($offers);
        }
        $offerData = [
            'sku'                   => $shopSkusByOfferIds[$offer['offer_id']],
            'product-id'            => $offer['product_sku'],
            'product-id-type'       => 'SKU',
            'description'           => $offer['description'],
            'price'                 => $offer['origin_price'],
            'price-additional-info' => $offer['price_additional_info'],
            'quantity'              => $offer['quantity'],
            'min-quantity-alert '   => '',
            'state'                 => 11,
            'active'                => $offer['active'],
            'available-start-date'  => formatDate($offer['available_start_date']),
            'available-end-date'    => formatDate($offer['available_end_date']),
            'logistic-class'        => $offer['logistic_class'],
            'favorite-rank'         => $offer['favorite_rank'] > 0 ? $offer['favorite_rank'] : '',
            'discount-price'        => $offer['discount_price'] > 0 ? $offer['discount_price'] : '',
            'discount-start-date'   => formatDate($offer['discount_start_date']),
            'discount-end-date'     => formatDate($offer['discount_end_date']),
            'discount-ranges'       => $offer['discount_ranges'],
            'product-tax-code'      => $offer['product_tax_code'],
            'allow-quote-request'   => $offer['allow_quote_requests'],
            'leadtime-to-ship'      => $offer['leadtime_to_ship'],
            'min-order-quantity'    => $offer['min_order_quantity'] ?: '',
            'max-order-quantity'    => $offer['max_order_quantity'] ?: '',
            'package-quantity'      => $offer['package_quantity'] ?: '',
            'price-ranges'          => $offer['price_ranges'],
            'clearance'             => '',
            'measurement_units'     => ''
        ];
        if ($offer['additional_info']) {
            $additionalInfo = json_decode($offer['additional_info'], true);
            foreach ($additionalInfo as $key => $value) {
                $offerData[$key] = $value;
            }
        }
        $data[] = $offerData;
    }
    if (!$data) {
        echo "No data for seller {$shopId}\n";
        continue;
    }

    // Add columns in top of file
    $cols = array_keys(reset($data));
    array_unshift($data, $cols);

    $file = \Mirakl\create_temp_csv_file($data);
    $request = new OfferImportRequest($file);
    $request->setImportMode(ImportMode::NORMAL);
    $request->setWithProducts(false);
    $request->setFileName($shopId . '-MGT-OF01-' . time() . '.csv');
    $request->setShop($shopId);
    $request->bodyParams[] = 'shop';

    $client = $offerApi->getClient($miraklConnection);
    $client->raw(false);

    if ($requestLogValidator->validate($request)) {
        $logger = $loggerManager->getLogger();
        $messageFormatter = $loggerManager->getMessageFormatter();
        $client->setLogger($logger, $messageFormatter);
    }

    $filepath = implode(DIRECTORY_SEPARATOR, [BP, 'var', 'mirakl', 'offer_import', $shopId . '.csv']);
    if (!$fh = @fopen($filepath, 'w+')) {
        echo "Can't open {$filepath} for write\n";
        continue;
    }

    $file->rewind();
    while (!$file->eof()) {
        fwrite($fh, $file->fgets());
    }
    fclose($fh);
    echo "Saved seller {$shopId} data to {$filepath}\n";

    $result = $request->run($client);
    echo "Seller {$shopId}: Import id {$result->getImportId()}\n";

    $result = $offerApi->getOffersImportResult($miraklConnection, $result->getImportId());
    foreach ($result->getData() as $key => $value) {
        if ($key == 'date_created') {
            continue;
        }
        $shops[$shopId][$key] = $value;
    }
}

// Add columns in top of file
$cols = array_keys(reset($shops));
array_unshift($shops, $cols);
$file = \Mirakl\create_temp_csv_file($shops);
$resultSavePath = implode(DIRECTORY_SEPARATOR, [BP, 'var', 'mirakl']);
$filepath = $resultSavePath . DIRECTORY_SEPARATOR . 'affected_sellers.csv';
if (!$fh = @fopen($filepath, 'w+')) {
    echo "Can't open {$filepath} for write\n";
}

$file->rewind();
while (!$file->eof()) {
    fwrite($fh, $file->fgets());
}
fclose($fh);
echo "Saved affected sellers data to {$filepath}\n";

function formatDate($value)
{
    if (!$value || $value == '0000-00-00 00:00:00') {
        return '';
    }
    return (new \DateTime($value))->format('Y-m-d\TH:i:s\Z');
}

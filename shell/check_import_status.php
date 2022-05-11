<?php
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

use Magento\Framework\App\Bootstrap;

require 'app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
/** @var \Magento\Framework\App\ResourceConnection $resource */
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();

/** @var \MiraklSeller\Api\Helper\Offer $offerApi */
$offerApi = $objectManager->get('MiraklSeller\Api\Helper\Offer');

$miraklConnectionFactory = $objectManager->get('MiraklSeller\Api\Model\ConnectionFactory');
/** @var \MiraklSeller\Api\Model\Connection $miraklConnection */
$miraklConnection = $miraklConnectionFactory->create();
$miraklConnection->load(2);

$resultSavePath = implode(DIRECTORY_SEPARATOR, [BP, 'var', 'mirakl']);
$filepath = $resultSavePath . DIRECTORY_SEPARATOR . 'affected_sellers.csv';
$file = fopen($filepath, "r");
$importIds = [];
while (($data = fgetcsv($file, 0, ";")) !== false) {
    if (isset($data[4]) && is_numeric($data[4])) {
        $importIds[] = $data[4];
    }
}

$imports = [];
foreach ($importIds as $importId) {
    $result = $offerApi->getOffersImportResult($miraklConnection, $importId);
    foreach ($result->getData() as $key => $value) {
        if ($key == 'date_created') {
            continue;
        }
        $imports[$importId][$key] = $value;
    }
}

// Add columns in top of file
$cols = array_keys(reset($imports));
array_unshift($imports, $cols);
$file = \Mirakl\create_temp_csv_file($imports);
$resultSavePath = implode(DIRECTORY_SEPARATOR, [BP, 'var', 'mirakl']);
$filepath = $resultSavePath . DIRECTORY_SEPARATOR . 'import_status.csv';
if (!$fh = @fopen($filepath, 'w+')) {
    echo "Can't open {$filepath} for write\n";
}

$file->rewind();
while (!$file->eof()) {
    fwrite($fh, $file->fgets());
}
fclose($fh);
echo "Saved import status to {$filepath}\n";

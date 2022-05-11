<?php
/**
 * TradeSquare
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

use Magento\Framework\App\Bootstrap;
use Mirakl\Core\Model\Shop;
use Mirakl\Core\Model\ResourceModel\Shop\Collection as ShopCollection;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;

require 'app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$data = [];
/** @var ShopCollectionFactory $shopCollectionFactory */
$shopCollectionFactory = $objectManager->get(ShopCollectionFactory::class);

/** @var ShopCollection $shopCollection */
$shopCollection = $shopCollectionFactory->create();

/** @var Shop $shop */
foreach ($shopCollection as $shop) {
    $info = $shop->getAdditionalInfo();
    $professionalInfo = isset($info['professional_info']) ? (array) $info['professional_info'] : [];
    $contactInfo = isset($info['contact_info']) ? (array) $info['contact_info'] : [];
    $paymentInfo = isset($info['payment_info']) ? (array) $info['payment_info'] : [];
    $paymentInfo = isset($info['payment_info']) ? (array) $info['payment_info'] : [];

    $data[$shop->getId()] = [
        'id'                  => $shop->getId(),
        'name'                => $shop->getName(),
        'registration_number' => $professionalInfo['identification_number'] ?? '',
        'street_1'            => $contactInfo['street_1'] ?? '',
        'street_2'            => $contactInfo['street_2'] ?? '',
        'zip_code'            => $contactInfo['zip_code'] ?? '',
        'city'                => $contactInfo['city'] ?? '',
        'country'             => $contactInfo['country'] ?? '',
        'website'             => $contactInfo['web_site'] ?? '',
        'phone'               => $contactInfo['phone'] ?? '',
        'payment_type'        => $paymentInfo['@type'] ?? '',
        'bank_name'           => $paymentInfo['bank_name'] ?? '',
        'owner'               => $paymentInfo['owner'] ?? '',
        'bsb_code'            => $paymentInfo['bsb_code'] ?? '',
        'bsb_ban'             => $paymentInfo['bsb_ban'] ?? '',
    ];
}

if ($data) {
    $newFileName = 'var/export/sellers_data_' . date('Y_m_d_H_i_s') . '.csv';
    $fpNewFile = fopen($newFileName, 'w');

    fputcsv($fpNewFile, array_keys(reset($data)));
    foreach ($data as $row) {
        fputcsv($fpNewFile, $row);
    }
}

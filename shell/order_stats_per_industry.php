<?php
/**
 * TradeSquare
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Bootstrap;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mirakl\Core\Model\ResourceModel\Shop\Collection as ShopCollection;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;

require 'app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$data = [];
/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);
/** @var OrderItemRepositoryInterface $orderItemRepository */
$orderItemRepository = $objectManager->get(OrderItemRepositoryInterface::class);
/** @var FilterBuilder $filterBuilder */
$filterBuilder = $objectManager->create(FilterBuilder::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->create(SearchCriteriaBuilder::class);
/** @var CustomerRepositoryInterface $customerRepository */
$customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
/** @var ShopCollectionFactory $shopCollectionFactory */
$shopCollectionFactory = $objectManager->get(ShopCollectionFactory::class);
/** @var EavConfig $eavConfig */
$eavConfig = $objectManager->get(EavConfig::class);

$businessTypeAttribute = $eavConfig->getAttribute('customer', 'business_type');
$sellGoodsAttribute = $eavConfig->getAttribute('customer', 'sell_goods');

/** Load all orders */
$searchCriteria = $searchCriteriaBuilder
    ->addFilter(OrderInterface::CREATED_AT, '2021-08-01', 'gt')
    ->create();
$orders = $orderRepository->getList($searchCriteria)->getItems();

$customerIds = [];
$orderIds = [];
foreach ($orders as $order) {
    if (!$order->getCustomerId()) {
        continue;
    }
    $customerIds[$order->getCustomerId()] = $order->getCustomerId();
    $orderIds[] = $order->getEntityId();
}

/** Load all related order items */
$searchCriteria = $searchCriteriaBuilder
    ->addFilter(OrderItemInterface::ORDER_ID, $orderIds, 'in')
    ->create();
$orderItems = $orderItemRepository->getList($searchCriteria)->getItems();

$miraklShopIds = [];
foreach ($orderItems as $orderItem) {
    $miraklShopId = $orderItem->getMiraklShopId();
    if (!$miraklShopId) {
        continue;
    }
    $miraklShopIds[$miraklShopId] = $miraklShopId;
}

/** Load all related shops */
/** @var ShopCollection $shopCollection */
$shopCollection = $shopCollectionFactory->create();
$shopCollection->addFieldToFilter('id', ['in' => $miraklShopIds]);

/** Find boutique orders */
$boutiqueOrders = [];
foreach ($orderItems as $orderItem) {
    $shop = $shopCollection->getItemById($orderItem->getMiraklShopId());
    if (!$shop) {
        continue;
    }

    if (strpos($shop->getDifferentiators(), 'Boutique') !== false) {
        $boutiqueOrders[$orderItem->getOrderId()] = $orderItem->getOrderId();
    }
}

/** Load all related customers */
$searchCriteria = $searchCriteriaBuilder
    ->addFilter('entity_id', $customerIds, 'in')
    ->create();
$customers = $customerRepository->getList($searchCriteria)->getItems();

/** Save all customers per id */
$customersById = [];
foreach ($customers as $customer) {
    $customersById[$customer->getId()] = $customer;
}

/** Assign orders to customers */
$ordersByCustomerId = [];
foreach ($orders as $order) {
    if (!isset($customersById[$order->getCustomerId()])) {
        continue;
    }
    $customer = $customersById[$order->getCustomerId()];
    $ordersByCustomerId[$customer->getId()][] = $order;
}

/** Collect data per industry */
$dataPerIndustry = [];
foreach ($customersById as $customer) {
    $orders = $ordersByCustomerId[$customer->getId()];
    if (!array($orders)) {
        continue;
    }

    if ($customer->getCustomAttribute('industry')) {
        $industries = explode(',', $customer->getCustomAttribute('industry')->getValue());
    } else {
        $industries = [];
    }

    /** @var OrderInterface $order */
    foreach ($orders as $order) {
        foreach ($industries as $industry) {
            if (!isset($dataPerIndustry[$industry])) {
                $dataPerIndustry[$industry] = [
                    'industry'                       => $industry,
                    'total_orders'                   => 0,
                    'total_amount'                   => 0,
                    'total_boutique_orders'          => 0,
                    'customers_with_multiple_orders' => 0
                ];
            }

            $dataPerIndustry[$industry]['total_orders']++;
            $dataPerIndustry[$industry]['total_amount'] += $order->getBaseGrandTotal();
            if (isset($boutiqueOrders[$order->getEntityId()])) {
                $dataPerIndustry[$industry]['total_boutique_orders']++;
            }
        }
    }

    if (count($orders) > 1) {
        foreach ($industries as $industry) {
            $dataPerIndustry[$industry]['customers_with_multiple_orders']++;
        }
    }
}

if ($dataPerIndustry) {
    $newFileName = 'var/export/order_stats_per_industry_' . date('Y_m_d_H_i_s') . '.csv';
    $fpNewFile = fopen($newFileName, 'w');

    fputcsv($fpNewFile, array_keys(reset($dataPerIndustry)));
    foreach ($dataPerIndustry as $row) {
        $row['total_amount'] = number_format($row['total_amount'], 2, '.', '');
        fputcsv($fpNewFile, $row);
    }
}

/** Collect data per business type */
$dataPerBusinessType = [];
foreach ($customersById as $customer) {
    $orders = $ordersByCustomerId[$customer->getId()];
    if (!array($orders)) {
        continue;
    }

    if ($customer->getCustomAttribute('business_type')) {
        $businessTypes = explode(',', $customer->getCustomAttribute('business_type')->getValue());
    } else {
        $businessTypes = [];
    }

    /** @var OrderInterface $order */
    foreach ($orders as $order) {
        foreach ($businessTypes as $businessType) {
            $businessTypeLabel = $businessTypeAttribute->getSource()->getOptionText($businessType);
            if (!isset($dataPerBusinessType[$businessType])) {
                $dataPerBusinessType[$businessType] = [
                    'business_type'                  => $businessTypeLabel,
                    'total_orders'                   => 0,
                    'total_amount'                   => 0,
                    'total_boutique_orders'          => 0,
                    'customers_with_multiple_orders' => 0
                ];
            }

            $dataPerBusinessType[$businessType]['total_orders']++;
            $dataPerBusinessType[$businessType]['total_amount'] += $order->getBaseGrandTotal();
            if (isset($boutiqueOrders[$order->getEntityId()])) {
                $dataPerBusinessType[$businessType]['total_boutique_orders']++;
            }
        }
    }

    if (count($orders) > 1) {
        foreach ($businessTypes as $businessType) {
            $dataPerBusinessType[$businessType]['customers_with_multiple_orders']++;
        }
    }
}

if ($dataPerBusinessType) {
    $newFileName = 'var/export/order_stats_per_business_type_' . date('Y_m_d_H_i_s') . '.csv';
    $fpNewFile = fopen($newFileName, 'w');

    fputcsv($fpNewFile, array_keys(reset($dataPerBusinessType)));
    foreach ($dataPerBusinessType as $row) {
        $row['total_amount'] = number_format($row['total_amount'], 2, '.', '');
        fputcsv($fpNewFile, $row);
    }
}

/** Collect data per sell_goods */
$dataPerSellGoods = [];
foreach ($customersById as $customer) {
    $orders = $ordersByCustomerId[$customer->getId()];
    if (!array($orders)) {
        continue;
    }

    if ($customer->getCustomAttribute('sell_goods')) {
        $sellGoodsValues = explode(',', $customer->getCustomAttribute('sell_goods')->getValue());
    } else {
        $sellGoodsValues = [0];
    }

    /** @var OrderInterface $order */
    foreach ($orders as $order) {
        foreach ($sellGoodsValues as $sellGoodsValue) {
            $sellGoodsValueLabel = $sellGoodsAttribute->getSource()->getOptionText($sellGoodsValue);
            if (!isset($dataPerSellGoods[$sellGoodsValue])) {
                $dataPerSellGoods[$sellGoodsValue] = [
                    'business_type'                  => $sellGoodsValueLabel,
                    'total_orders'                   => 0,
                    'total_amount'                   => 0,
                    'total_boutique_orders'          => 0,
                    'customers_with_multiple_orders' => 0
                ];
            }

            $dataPerSellGoods[$sellGoodsValue]['total_orders']++;
            $dataPerSellGoods[$sellGoodsValue]['total_amount'] += $order->getBaseGrandTotal();
            if (isset($boutiqueOrders[$order->getEntityId()])) {
                $dataPerSellGoods[$sellGoodsValue]['total_boutique_orders']++;
            }
        }
    }

    if (count($orders) > 1) {
        foreach ($sellGoodsValues as $sellGoodsValue) {
            $dataPerSellGoods[$sellGoodsValue]['customers_with_multiple_orders']++;
        }
    }
}

if ($dataPerSellGoods) {
    $newFileName = 'var/export/order_stats_per_sell_goods_' . date('Y_m_d_H_i_s') . '.csv';
    $fpNewFile = fopen($newFileName, 'w');

    fputcsv($fpNewFile, array_keys(reset($dataPerSellGoods)));
    foreach ($dataPerSellGoods as $row) {
        $row['total_amount'] = number_format($row['total_amount'], 2, '.', '');
        fputcsv($fpNewFile, $row);
    }
}

<?php
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

use Magento\Framework\App\Bootstrap;
use Magento\Store\Model\Store;

require '../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$entityType = 'catalog_product';
$directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
$path = $directory->getRoot() . '/pub/scripts/';

$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();
$helper = $objectManager->get('\Vdcstore\CategoryTree\Helper\Data');
$menuRootId = $helper->getMenuRoot();

$miraklConfigHelper = $objectManager->get('\Mirakl\Mci\Helper\Config');
$miraklRootId = $miraklConfigHelper->getHierarchyRootCategoryId();

$categoryCollection = $objectManager->get('\Magento\Catalog\Model\CategoryFactory')->create()->getCollection();
$categoryCollection->addFieldToSelect('entity_id');
$categoryCollection->addFieldToSelect('parent_id');
$categoryCollection->addAttributeToSelect('name', 'left');
$categoryCollection->addAttributeToSelect('display_mode', 'left');
$categoryCollection->addAttributeToSelect('available_sort_by', 'left');
$categoryCollection->addAttributeToSelect('default_sort_by', 'left');
$categoryCollection->addAttributeToSelect('page_layout', 'left');
$categoryCollection->addAttributeToSelect('url_key', 'left');
$categoryCollection->addAttributeToSelect('child_categories', 'left');
$categoryCollection->addAttributeToSelect('url_path', 'left');
$categoryCollection->addFieldToFilter('path', ['like' => "1/$menuRootId/%"]);
$categoryCollection->getSelect()->joinLeft(
    ['cat_index' => 'catalog_category_product_index_store1'],
    'cat_index.category_id=e.entity_id',
    ['product_count' => 'COUNT(DISTINCT cat_index.product_id)']
)->group('e.entity_id');

global $categoryWithName, $defaultCategoryWithName;
$sql = $categoryCollection->getSelect();
$categoryWithNameSelect = (clone $sql);
$categoryWithNameSelect->reset('columns')->columns(['entity_id', 'name' => 'IF(at_name.value_id > 0, at_name.value, at_name_default.value)']);
$categoryWithName = $connection->fetchAssoc($categoryWithNameSelect);

$defaultCategoryWithNameSelect = (clone $categoryWithNameSelect);
$defaultCategoryWithNameSelect->reset('where')->where("e.path LIKE '1/$miraklRootId/%'");
$defaultCategoryWithName = $connection->fetchAssoc($defaultCategoryWithNameSelect);
function getCatPath($path)
{
    global $categoryWithName;
    $paths = explode('/', $path);
    $namePaths = [];
    foreach ($paths as $path) {
        if (isset($categoryWithName[$path]['name'])) {
            $namePaths[] = $categoryWithName[$path]['name'];
        }
    }
    $namePath = implode('/', $namePaths);
    return $namePath;
}

function getChildCatName($childIds)
{
    if ($childIds) {
        global $defaultCategoryWithName;
        if (strpos($childIds, ',') !== false) {
            $childIds = explode(',', $childIds);
        } else {
            $childIds = [$childIds];
        }
        $defaultCategoryWithNames = [];
        foreach ($childIds as $childId) {
            if (isset($defaultCategoryWithName[$childId]['name'])) {
                $defaultCategoryWithNames[] = $defaultCategoryWithName[$childId]['name'];
            }
        }
        return implode('|', $defaultCategoryWithNames);
    }
    return "";
}

function getRowData($rows)
{
    $rowData = [
        'category_id'         => $rows['entity_id'],
        'store_id'            => Store::DEFAULT_STORE_ID,
        'parent'              => $rows['parent_id'],
        'name'                => $rows['name'],
        'display_mode'        => $rows['display_mode'],
        'available_sort_by'   => $rows['available_sort_by'],
        'default_sort_by'     => $rows['default_sort_by'],
        'page_layout'         => $rows['page_layout'],
        'image'               => "",
        'url_key'             => $rows['url_key'],
        'product_sku'         => "",
        'child_categories'    => implode("|", explode(",", $rows['child_categories'])),
        'child_category_name' => getChildCatName($rows['child_categories']),
        'category_path'       => "Menu Categories/" . getCatPath($rows['path']),
        'url_path'            => $rows['url_path'],
        'product_count'       => $rows['product_count'],
    ];
    return $rowData;
}

$data = [];
$headers = [
    'category_id'         => 'category_id',
    'store_id'            => 'store_id',
    'parent'              => 'parent',
    'name'                => 'name',
    'display_mode'        => 'display_mode',
    'available_sort_by'   => 'available_sort_by',
    'default_sort_by'     => 'default_sort_by',
    'page_layout'         => 'page_layout',
    'image'               => 'image',
    'url_key'             => 'url_key',
    'product_sku'         => 'product_sku',
    'child_categories'    => 'child_categories',
    'child_category_name' => 'child_category_name',
    'category_path'       => 'category_path',
    'url_path'            => 'url_path',
    'product_count'       => 'product_count'
];
//$data[] = $headers;
if (isset($_GET['export'])) {
    unset($headers['child_category_name']);
    unset($headers['category_path']);
    unset($headers['url_path']);
    unset($headers['product_count']);
    $result = $connection->query($sql);
    while ($rows = $result->fetch()) {
        $rowData = getRowData($rows);
        unset($rowData['child_category_name']);
        unset($rowData['category_path']);
        unset($rowData['url_path']);
        unset($rowData['product_count']);
        $data[] = $rowData;
    }
    $result = $data;
    export_data_to_csv($result, 'frontend_menu_category_list', ',');
} else {
    $result = $connection->query($sql);
}
?>
<button><a href="?export=1">Export Csv</a></button>

<table>
    <tr>
        <?php foreach ($headers as $header) { ?>
            <th><?php echo $header ?></th>
        <?php } ?>
    </tr>
    <?php while ($rows = $result->fetch()) { ?>
        <?php
        $rowData = getRowData($rows);
        $data[] = $rowData;
        ?>
        <tr>
            <?php foreach ($rowData as $row) { ?>
                <th><?php echo $row ?></th>
            <?php } ?>
        </tr>
    <?php } ?>
</table>


<style type="text/css">
    table, th, td {
        border: 1px solid black;
    }
    th {
        text-align: left;
    }
</style>
<?php

function export_data_to_csv($data, $filename = 'export', $delimiter = ';', $enclosure = '"')
{
    // Tells to the browser that a file is returned, with its name : $filename.csv
    header("Content-disposition: attachment; filename=$filename.csv");
    // Tells to the browser that the content is a csv file
    header("Content-Type: text/csv");

    // I open PHP memory as a file
    $fp = fopen("php://output", 'w');

    // Insert the UTF-8 BOM in the file
    fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

    // I add the array keys as CSV headers
    fputcsv($fp, array_keys($data[0]), $delimiter, $enclosure);

    // Add all the data in the file
    foreach ($data as $fields) {
        fputcsv($fp, $fields, $delimiter, $enclosure);
    }

    // Close the file
    fclose($fp);

    // Stop the script
    die();
}

?>

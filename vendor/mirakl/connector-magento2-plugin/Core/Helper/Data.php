<?php
namespace Mirakl\Core\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\ConfigurableFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ConfigurableFactory
     */
    protected $typeConfigurableFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param   Context                     $context
     * @param   StoreManagerInterface       $storeManager
     * @param   FilterManager               $filterManager
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   ConfigurableFactory         $typeConfigurableFactory
     * @param   ObjectManagerInterface      $objectManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        FilterManager $filterManager,
        ProductCollectionFactory $productCollectionFactory,
        ConfigurableFactory $typeConfigurableFactory,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->filterManager = $filterManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->typeConfigurableFactory = $typeConfigurableFactory;
        $this->objectManager = $objectManager;
    }

    /**
     * Adds a query parameter to specified URL
     *
     * @param   string  $url
     * @param   string  $param
     * @param   string  $value
     * @return  string
     */
    public function addQueryParamToUrl($url, $param, $value)
    {
        $pieces = parse_url($url);
        $queryParams = [];
        if (isset($pieces['query'])) {
            parse_str($pieces['query'], $queryParams);
        }
        $queryParams[$param] = $value;
        $pieces['query'] = http_build_query($queryParams);

        return $this->buildUrl($pieces);
    }

    /**
     * Builds URL string from specified pieces
     *
     * @param   array   $pieces
     * @return  string
     */
    public function buildUrl(array $pieces)
    {
        $scheme   = isset($pieces['scheme'])   ? $pieces['scheme'] . '://'  : '';
        $host     = isset($pieces['host'])     ? $pieces['host']            : '';
        $port     = isset($pieces['port'])     ? ':' . $pieces['port']      : '';
        $user     = isset($pieces['user'])     ? $pieces['user']            : '';
        $pass     = isset($pieces['pass'])     ? ':' . $pieces['pass']      : '';
        $pass     = ($user || $pass)           ? $pass . '@'                : '';
        $path     = isset($pieces['path'])     ? $pieces['path']            : '';
        $query    = isset($pieces['query'])    ? '?' . $pieces['query']     : '';
        $fragment = isset($pieces['fragment']) ? '#' . $pieces['fragment']  : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * @param   \DateTime    $date
     * @param   int         $format
     * @param   bool        $showTime
     * @return  string
     */
    public function formatDateTime(\DateTime $date, $format = \IntlDateFormatter::MEDIUM, $showTime = true)
    {
        return $this->getTimezone()->formatDate(\Mirakl\date_format($date), $format, $showTime);
    }

    /**
     * Format specified duration (in seconds) into human readable duration
     *
     * @param   int|\DateInterval    $duration
     * @return  string
     */
    public function formatDuration($duration)
    {
        if (!$duration) {
            return '';
        }

        if ($duration instanceof \DateInterval) {
            $days    = $duration->d;
            $hours   = $duration->h;
            $minutes = $duration->i;
            $seconds = $duration->s;
        } else {
            $days      = floor($duration / 86400);
            $duration -= $days * 86400;
            $hours     = floor($duration / 3600);
            $duration -= $hours * 3600;
            $minutes   = floor($duration / 60);
            $seconds   = floor($duration - $minutes * 60);
        }

        $duration = '';
        if ($days > 0) {
            $duration .= __('%1d', $days) . ' ';
        }
        if ($hours > 0) {
            $duration .= __('%1h', $hours) . ' ';
        }
        if ($minutes > 0) {
            $duration .= __('%1m', $minutes) . ' ';
        }
        if ($seconds > 0) {
            $duration .= __('%1s', $seconds);
        }

        return trim($duration);
    }

    /**
     * Formats given size (in bytes) into an easy readable size
     *
     * @param   int     $size
     * @param   string  $separator
     * @return  string
     */
    public function formatSize($size, $separator = ' ')
    {
        $unit = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $size = round($size / pow(1024, ($k = intval(floor(log($size, 1024))))), 2) . $separator . $unit[$k];

        return $size;
    }

    /**
     * Returns base media URL for specified store
     *
     * @param   mixed   $store
     * @return  string
     */
    public function getBaseMediaUrl($store = null)
    {
        return $this->getStore($store)->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Returns base URL for specified store
     *
     * @param   mixed   $store
     * @return  string
     */
    public function getBaseUrl($store = null)
    {
        return $this->getStore($store)->getBaseUrl(UrlInterface::URL_TYPE_DIRECT_LINK);
    }

    /**
     * Formats given date into an easy readable date
     *
     * @param   string|\DateTime    $date
     * @return  string
     */
    public function getFullDate($date)
    {
        if ($date instanceof \DateTime) {
            $date = $date->format(\DateTime::ISO8601);
        }

        return $this->getTimezone()->formatDate($date, \IntlDateFormatter::FULL, true);
    }

    /**
     * @return  TimezoneInterface
     */
    public function getTimezone()
    {
        return $this->objectManager->get(TimezoneInterface::class);
    }

    /**
     * Returns number of seconds between now and given date, formatted into readable duration if needed
     *
     * @param   mixed   $date
     * @param   bool    $toDuration
     * @return  int|string
     */
    public function getMoment($date, $toDuration = true)
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        $now = new \DateTime();
        $seconds = $now->getTimestamp() - $date->getTimestamp();

        return $toDuration ? $this->formatDuration($seconds) : $seconds;
    }

    /**
     * @param   Product $product
     * @return  Product|null
     */
    public function getParentProduct(Product $product)
    {
        $parent = null;
        if ($product->getTypeId() == 'simple') {
            $parentIds = $this->typeConfigurableFactory->create()->getParentIdsByChild($product->getId());

            $collection = $this->productCollectionFactory->create();
            $collection->addIdFilter($parentIds);

            // Get first parent product if possible
            if ($collection->count()) {
                /** @var Product $parent */
                $parent = $collection->getFirstItem();
                $parent->setStoreId(0);
            }
        }

        return $parent;
    }

    /**
     * @param   mixed   $store
     * @return  Store
     */
    public function getStore($store = null)
    {
        return $this->storeManager->getStore($store);
    }

    /**
     * Returns current version of the Magento Connector
     *
     * @return  string
     */
    public function getVersion()
    {
        $file = MIRAKL_BP . DIRECTORY_SEPARATOR . 'composer.json';
        preg_match('#"version":\s+"(\d+\.\d+\.\d+-?.*)"#', file_get_contents($file), $matches);

        return isset($matches[1]) ? $matches[1] : '';
    }

    /**
     * Returns current version of the PHP SDK used by the Magento Connector
     *
     * @return  string
     */
    public function getVersionSDK()
    {
        $matches = [];
        $packages = ['sdk-php-front', 'sdk-php', 'sdk-php-operator']; // try different package names
        foreach ($packages as $package) {
            $file = implode(DIRECTORY_SEPARATOR, [BP, 'vendor', 'mirakl', $package, 'composer.json']);
            if (file_exists($file)) {
                preg_match('#"version":\s+"(\d+\.\d+\.\d+-?.*)"#', file_get_contents($file), $matches);
            }
        }

        return isset($matches[1]) ? $matches[1] : '';
    }

    /**
     * Checks if specified attribute is using options or not
     *
     * @param   AbstractAttribute   $attribute
     * @return  bool
     */
    public function isAttributeUsingOptions(AbstractAttribute $attribute)
    {
        $model = $attribute->getSource();
        $backend = $attribute->getBackendType();

        return $attribute->usesSource() &&
            ($backend == 'int' && $model instanceof \Magento\Eav\Model\Entity\Attribute\Source\Table) ||
            ($backend == 'varchar' && $attribute->getFrontendInput() == 'multiselect');
    }

    /**
     * @return  bool
     */
    public static function isEnterprise()
    {
        return class_exists('Magento\Enterprise\Model\ProductMetadata');
    }

    /**
     * Truncates a string to a certain length if necessary, appending the $etc string.
     * $remainder will contain the string that has been replaced with $etc.
     *
     * @param   string  $value
     * @param   int     $length
     * @param   string  $etc
     * @param   string  $remainder
     * @param   bool    $breakWords
     * @return  string
     */
    public function truncate($value, $length = 80, $etc = '...', &$remainder = '', $breakWords = true)
    {
        return $this->filterManager->truncate($value, [
            'length' => $length, 'etc' => $etc, 'remainder' => $remainder, 'breakWords' => $breakWords
        ]);
    }
}

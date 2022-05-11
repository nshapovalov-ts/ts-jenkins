<?php

namespace Mirakl\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\Core\Model\ResourceModel\ShopFactory as ShopResourceFactory;
use Mirakl\Core\Model\Shop;
use Mirakl\Core\Model\ShopFactory;

class Csv extends AbstractHelper
{
    /**
     * @var ShopFactory
     */
    protected $shopFactory;

    /**
     * @var ShopResourceFactory
     */
    protected $shopResourceFactory;

    /**
     * @param   Context             $context
     * @param   ShopFactory         $shopFactory
     * @param   ShopResourceFactory $shopResourceFactory
     */
    public function __construct(
        Context $context,
        ShopFactory $shopFactory,
        ShopResourceFactory $shopResourceFactory
    ) {
        parent::__construct($context);
        $this->shopFactory = $shopFactory;
        $this->shopResourceFactory = $shopResourceFactory;
    }

    /**
     * Retrieve import id from CSV file name
     *
     * @param   string  $fileName
     * @return  int
     * @throws  LocalizedException
     */
    public function getImportIdFromFileName($fileName)
    {
        preg_match('/^\d+-(\d+)-.+/i', $fileName, $matches);
        if (!isset($matches[1])) {
            throw new LocalizedException(__('Could not find import id from file name "%1"', $fileName));
        }

        return (int) $matches[1];
    }

    /**
     * Retrieve shop from CSV file name
     *
     * @param   string  $fileName
     * @return  Shop
     * @throws  LocalizedException
     */
    public function getShopFromFileName($fileName)
    {
        preg_match('/^(\d+).+/i', $fileName, $matches);
        if (!isset($matches[1])) {
            throw new LocalizedException(__('Could not find shop id from file name "%1"', $fileName));
        }

        // Shop Id validation
        $shopId = $matches[1];
        $shop = $this->shopFactory->create();
        $this->shopResourceFactory->create()->load($shop, $shopId);

        if (!$shop->getId()) {
            throw new LocalizedException(
                __('Could not find shop id "%1" in Magento. See your Mirakl configuration.', $shopId)
            );
        }

        return $shop;
    }

    /**
     * Splits specified CSV file into N files and returns the number of created files
     *
     * @param   \SplFileObject  $file
     * @param   int             $size
     * @param   \Closure        $callback
     * @return  int
     */
    public function split(\SplFileObject $file, $size, \Closure $callback = null)
    {
        $size = intval($size);
        if (!$size || !$file->getFlags() & \SplFileObject::READ_CSV) {
            return 0;
        }

        $i     = 0;
        $count = 0;
        $cols  = $file->fgetcsv();
        $data  = [$cols];

        while (!$file->eof() && ++$i) {
            $data[] = $file->fgetcsv();

            if ($i % $size === 0 || $file->eof()) {
                $tmpFile = new FileWrapper($data); // create a CSV file from given data
                if ($callback) {
                    $callback($tmpFile->getFile());
                }
                $data = [$cols];
                $count++;
            }
        }

        return $count;
    }

    /**
     * Validates that given file exists and is a valid CSV file
     *
     * @param   string  $file
     * @throws  LocalizedException
     */
    public function validateFile($file)
    {
        if (!is_file($file)) {
            throw new LocalizedException(__('Specified file "%s" was not found', $file));
        }

        if (!filesize($file)) {
            throw new LocalizedException(__('Specified file "%s" is empty', $file));
        }

        if (!is_readable($file)) {
            throw new LocalizedException(__('Specified file "%s" is not readable', $file));
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if ($extension !== 'csv' && $extension !== 'txt') {
            throw new LocalizedException(__('File of type "%s" is not allowed', $extension));
        }
    }
}

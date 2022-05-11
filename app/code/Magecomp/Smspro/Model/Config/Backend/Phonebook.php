<?php

namespace Magecomp\Smspro\Model\Config\Backend;

use Magecomp\Smspro\Model\PhonebookFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Phonebook extends \Magento\Framework\App\Config\Value
{
    protected $_phonebookModel;
    protected $_dbConnection;
    protected $_messageManager;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        PhonebookFactory $phonebookModel,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        Filesystem $filesystem,
        ReadFactory $readFactory,
        ResourceConnection $connection,
        ManagerInterface $messageManager,
        $data = array()
    )
    {
        $this->_phonebookModel = $phonebookModel;
        $this->_filesystem = $filesystem;
        $this->_readFactory = $readFactory;
        $this->_dbConnection = $connection;
        $this->_messageManager = $messageManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function afterSave()
    {
        try {
            if(empty(strpos($this['fieldset_data']['importcsv']['type'], 'csv'))!==false)
            {
                return $this;
            }
            if (strpos($this['fieldset_data']['importcsv']['type'], 'csv') === false) {
                $this->_messageManager->addError(__("You have uploaded an invalid file type, Upload only csv file."));
                return $this;
            }

            $uploaders = ObjectManager::getInstance()->create('Magento\MediaStorage\Model\File\Uploader', ['fileId' => $this['fieldset_data']['importcsv']]);
            $uploadvalue = $uploaders->validateFile();
            $csvFile = $uploadvalue['tmp_name'];

            $tmpDirectory = ini_get('upload_tmp_dir') ? $this->_readFactory->create(ini_get('upload_tmp_dir'))
                : $this->_filesystem->getDirectoryRead(DirectoryList::SYS_TMP);

            $path = $tmpDirectory->getRelativePath($csvFile);
            $stream = $tmpDirectory->openFile($path);
            $headers = $stream->readCsv();
            if ($headers === false || count($headers) < 2) {
                $stream->close();
                throw new \Magento\Framework\Exception\LocalizedException(__('Please correct csv File Format.'));
            }
            $arrayColumn = 0;
            $twodarray = array();

            while (false !== ($csvLine = $stream->readCsv())) {
                $twodarray[$arrayColumn][0] = $csvLine[0];
                $twodarray[$arrayColumn][1] = $csvLine[1];
                $arrayColumn++;
            }

            $totalRow = $this->_saveImportData($twodarray, $arrayColumn);
            $stream->close();
            $this->_messageManager->addSuccess(__("Successfully Imported $totalRow Zipcode."));
            return parent::afterSave();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function _saveImportData( $data, $arrayColumn )
    {
        try {

            $totalRow = 0;
            for ($i = 0; $i < $arrayColumn; $i++) {
                $codModel = $this->_phonebookModel->create();
                $codModel->setName($data[$i][0]);
                $codModel->setMobile($data[$i][1]);
                $codModel->save();
                $totalRow++;
            }
            return $totalRow;
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }
}


<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_ImportExportCategories
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\ImportExportCategories\Plugin\Controller\Import;

use Closure;
use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\ImportExport\Controller\Adminhtml\Import\Download as ImportDownload;

/**
 * Class Download
 * @package Mageplaza\ImportExportCategories\Plugin\Controller\Import
 */
class Download
{
    /**
     * Import file name
     */
    const IMPORT_FILE = 'mageplaza_categories_import';
    /**
     * Module name
     */
    const SAMPLE_FILES_MODULE = 'Mageplaza_ImportExportCategories';

    /**
     * @var Http
     */
    protected $_request;

    /**
     * @var RawFactory
     */
    protected $_resultRawFactory;

    /**
     * @var ReadFactory
     */
    protected $_readFactory;

    /**
     * @var ComponentRegistrar
     */
    protected $_componentRegistrar;

    /**
     * @var FileFactory
     */
    protected $_fileFactory;

    /**
     * @var RedirectFactory
     */
    protected $_resultRedirectFact;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * Download constructor.
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param RawFactory $resultRawFactory
     * @param ReadFactory $readFactory
     * @param ComponentRegistrar $componentRegistrar
     * @param Http $request
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        RawFactory $resultRawFactory,
        ReadFactory $readFactory,
        ComponentRegistrar $componentRegistrar,
        Http $request
    ) {
        $this->_fileFactory        = $fileFactory;
        $this->_resultRawFactory   = $resultRawFactory;
        $this->_readFactory        = $readFactory;
        $this->_componentRegistrar = $componentRegistrar;
        $this->_request            = $request;
        $this->_resultRedirectFact = $context->getResultRedirectFactory();
        $this->_messageManager     = $context->getMessageManager();
    }

    /**
     * @param ImportDownload $download
     * @param Closure $proceed
     *
     * @return Redirect|Raw
     * @throws Exception
     * @SuppressWarnings(Unused)
     */
    public function aroundExecute(ImportDownload $download, Closure $proceed)
    {
        if ($this->_request->getParam('filename') != self::IMPORT_FILE) {
            return $proceed();
        }

        $fileName         = $this->_request->getParam('filename') . '.csv';
        $moduleDir        = $this->_componentRegistrar->getPath(ComponentRegistrar::MODULE, self::SAMPLE_FILES_MODULE);
        $fileAbsolutePath = $moduleDir . '/Files/Sample/' . $fileName;
        $directoryRead    = $this->_readFactory->create($moduleDir);
        $filePath         = $directoryRead->getRelativePath($fileAbsolutePath);

        if (!$directoryRead->isFile($filePath)) {
            /** @var Redirect $resultRedirect */
            $this->_messageManager->addErrorMessage(__('There is no sample file for this entity.'));
            $resultRedirect = $this->_resultRedirectFact->create();
            $resultRedirect->setPath('*/import');

            return $resultRedirect;
        }

        $fileSize = isset($directoryRead->stat($filePath)['size'])
            ? $directoryRead->stat($filePath)['size'] : null;

        $this->_fileFactory->create(
            $fileName,
            null,
            DirectoryList::VAR_DIR,
            'application/octet-stream',
            $fileSize
        );

        /** @var Raw $resultRaw */
        $resultRaw = $this->_resultRawFactory->create();
        $resultRaw->setContents($directoryRead->readFile($filePath));

        return $resultRaw;
    }
}

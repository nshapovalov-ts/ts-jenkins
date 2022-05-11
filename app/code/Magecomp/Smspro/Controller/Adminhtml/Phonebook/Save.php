<?php

namespace Magecomp\Smspro\Controller\Adminhtml\Phonebook;

use Magecomp\Smspro\Model\Phonebook;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;

class Save extends Action
{
    protected $_fileUploaderFactory;
    protected $__filesystem;
    protected $_extensionModel;

    public function __construct(
        Context $context,
        Phonebook $extensionModel,
        UploaderFactory $fileUploaderFactory,
        Filesystem $filesystem
    )
    {
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_filesystem = $filesystem;
        $this->_extensionModel = $extensionModel;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->_redirect('magecompsms/phonebook/index');
        if ($this->getRequest()->getPostValue()) {
            try {
                $model = $this->_extensionModel;
                $data = $this->getRequest()->getPostValue();

                $inputFilter = new \Zend_Filter_Input(
                    [],
                    [],
                    $data
                );
                $data = $inputFilter->getUnescaped();
                $id = $this->getRequest()->getParam('id');

                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('This store is no longer available.'));
                    }
                }

                $model->setData($data);

                $session = $this->_session;
                $session->setPageData($model->getData());
                $model->save();
                $this->messageManager->addSuccess(__('Phonebook Record Edited Succssfully.'));
                $session->setPageData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('magecompsms/phonebook/edit', ['id' => $model->getId()]);
                    return;
                }

                $this->_redirect('magecompsms/phonebook/index');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('id');
                if (!empty($id)) {
                    $this->_redirect('magecompsms/phonebook/edit', ['id' => $id]);
                } else {
                    $this->_redirect('magecompsms/phonebook/new');
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while saving the store information.' . $e)
                );
                $this->_session->setPageData($data);
                $this->_redirect('magecompsms/phonebook/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->_redirect('magecompsms/phonebook/index');
    }

    protected function _isAllowed()
    {
        return true;
    }
}
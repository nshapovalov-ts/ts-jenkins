<?php

/**
 * EYEMAGINE - The leading Magento Solution Partner
 *
 * HubSpot Integration with Magento
 *
 * @author    EYEMAGINE <magento@eyemaginetech.com>
 * @copyright Copyright (c) 2016 EYEMAGINE Technology, LLC (http://www.eyemaginetech.com)
 * @license   http://www.eyemaginetech.com/license
 */
namespace Eyemagine\HubSpot\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Eyemagine\HubSpot\Model\Config\Backend\Keys;
use Magento\Framework\App\Cache\TypeListInterface;

/**
 * Class Index
 *
 * @package Eyemagine\HubSpot\Controller\Adminhtml\Index
 */
class Index extends \Magento\Backend\App\Action
{


    /**
     *
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     *
     * @var \Eyemagine\HubSpot\Model\Config\Backend\Keys
     */
    protected $keys;

    /** @var \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList */
    protected $cacheTypeList;

    /**
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Eyemagine\HubSpot\Model\Config\Backend\Keys $keys
     */
    public function __construct(
        Context $context,
        Keys $keys,
        TypeListInterface $cacheTypeList
    ) {
        parent::__construct($context);
        
        $this->resultFactory = $context->getResultFactory();
        $this->keys = $keys;
        $this->cacheTypeList = $cacheTypeList;
    }



    /**
     * Generate access keys
     *
     * @return \Magento\Framework\Controller\Result
     */
    public function execute()
    {
        $this->keys->generateAccessKeys();
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        $this->cacheTypeList->cleanType('config');

        return $resultRedirect;
    }
}

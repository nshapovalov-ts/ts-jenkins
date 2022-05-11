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
namespace Eyemagine\HubSpot\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;

/**
 * Class AbstractSync
 *
 * @package Eyemagine\HubSpot\Controller
 */
abstract class AbstractSync extends Action implements SyncInterface
{

    /**
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(Context $context, JsonFactory $resultJsonFactory)
    {
        parent::__construct($context);

        // Magento 2.3 POST method fix (with previous Magento versions support)
        $request = $context->getRequest();
        if (interface_exists('\Magento\Framework\App\CsrfAwareActionInterface') && empty($request->getParam('form_key'))) {
            $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class);
            $request->setParam('form_key', $formKey->getFormKey());

            $postFields = array();
            parse_str($this->getRequest()->getContent(), $postFields);
            foreach ($postFields as $field => $value) {
                $request->setParam($field, $value);
            }
        }
        // eof - Magento 2.3 POST method fix (with previous Magento versions support)

        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Flushes the data as JSON response
     *
     * @param mixed $data
     */
    public function outputJson($data)
    {
        return $this->resultJsonFactory->create()->setData($data);
    }

    /**
     * Function used to output an error.
     *
     * @param integer $code
     * @param string $error
     * @param Exception $e
     */
    public function outputError($code, $error, Exception $e = null)
    {
        $data = (array(
            'error' => $error,
            'code' => $code,
            'extra' => ($e) ? $e->getMessage() : ''
        ));
        
        return $this->resultJsonFactory->create()->setData($data);
    }
}

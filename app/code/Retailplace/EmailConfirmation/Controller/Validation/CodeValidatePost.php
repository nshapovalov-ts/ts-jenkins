<?php

/**
 * Retailplace_EmailConfirmation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\EmailConfirmation\Controller\Validation;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Retailplace\AjaxResponse\Model\ResponseManagement;
use Retailplace\EmailConfirmation\Model\Validator;

/**
 * Class CodeValidatePost
 */
class CodeValidatePost extends Action implements HttpPostActionInterface
{
    /** @var \Retailplace\AjaxResponse\Model\ResponseManagement */
    private $responseManagement;

    /** @var \Retailplace\EmailConfirmation\Model\Validator */
    private $codeValidator;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Retailplace\AjaxResponse\Model\ResponseManagement $responseManagement
     * @param \Retailplace\EmailConfirmation\Model\Validator $codeValidator
     */
    public function __construct(
        Context $context,
        ResponseManagement $responseManagement,
        Validator $codeValidator
    ) {
        parent::__construct($context);

        $this->responseManagement = $responseManagement;
        $this->codeValidator = $codeValidator;
    }

    /**
     * Execute Controller
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $email = html_entity_decode($this->getRequest()->getParam('email'), ENT_QUOTES);
        $code = $this->getRequest()->getParam('code');

        if (!$email || !$code) {
            $response = $this->responseManagement->fail(
                __('Email and Code should be provided')
            );
        } else {
            $retryAfterSeconds = $this->codeValidator->checkSecuritySettings($email);
            if ($retryAfterSeconds !== null) {
                $response = $this->responseManagement->fail(
                    __('Maximum attempts has been exceeded. Please try again after %1 seconds', $retryAfterSeconds)
                );
            } else {
                $resultCustomer = $this->codeValidator->validateDigitalCode($email, $code);
                if ($resultCustomer) {
                    $response = $this->responseManagement->success([
                        'redirect_url' => $this->_url->getUrl('customer/account/confirm', [
                            'id' => $resultCustomer->getId(),
                            'key' => $resultCustomer->getConfirmation()
                        ])
                    ],
                        __('Email was approved successfully')
                    );
                } else {
                    $response = $this->responseManagement->fail(
                        __('Incorrect Validation Code')
                    );
                }
            }
        }

        return $response;
    }
}

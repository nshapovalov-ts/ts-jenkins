<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CustomerAccount\Controller\Account;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\FileProcessorFactory;
use Magento\Customer\Model\FileUploaderFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Url\DecoderInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\UrlInterface;

class ContactInfo extends AbstractAccount implements HttpPostActionInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var FileUploaderFactory
     */
    private $fileUploaderFactory;

    /**
     * @var CustomerMetadataInterface
     */
    private $customerMetadataService;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var FileProcessorFactory
     */
    private $fileProcessorFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlInterface
     */
    protected $urlModel;

    /**
     * @var DecoderInterface
     */
    private $urlDecoder;

    /**
     * UploadFile constructor.
     *
     * @param Context $context
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerMetadataInterface $customerMetadataService
     * @param FileProcessorFactory $fileProcessorFactory
     * @param FileUploaderFactory $fileUploaderFactory
     * @param LoggerInterface $logger
     * @param Session $session
     * @param UrlInterface $urlModel
     * @param DecoderInterface $urlDecoder
     */
    public function __construct(
        Context $context,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        CustomerMetadataInterface $customerMetadataService,
        FileProcessorFactory $fileProcessorFactory,
        FileUploaderFactory $fileUploaderFactory,
        LoggerInterface $logger,
        Session $session,
        UrlInterface $urlModel,
        DecoderInterface $urlDecoder
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerMetadataService = $customerMetadataService;
        $this->customerFactory = $customerFactory;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->session = $session;
        $this->logger = $logger;
        $this->fileProcessorFactory = $fileProcessorFactory;
        $this->urlModel = $urlModel;
        $this->urlDecoder = $urlDecoder;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $response = [
            'error' => false,
            'message' => __('Saved data successfully.')
        ];
        try {
            $customer = $this->customerRepository->getById($this->session->getCustomerId());
            $customer->setCustomAttribute('business_link', $this->getRequest()->getParam('business_link'));
            if (!empty($_FILES) && file_exists($_FILES['customer']['tmp_name']['upload_file'])) {

                $attributeCode = key($_FILES['customer']['name']);
                $attributeMetadata = $this->customerMetadataService->getAttributeMetadata($attributeCode);

                $fileUploader = $this->fileUploaderFactory->create([
                    'attributeMetadata' => $attributeMetadata,
                    'entityTypeCode' => CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                    'scope' => 'customer',
                ]);

                $errors = $fileUploader->validate();
                if (true !== $errors) {
                    $errorMessage = implode('</br>', $errors);
                    $response['error'] = true;
                    $response['message'] = $errorMessage;
                } else {
                    $fileInfo = $fileUploader->upload();
                    $this->moveTmpFileToSuitableFolder($fileInfo);
                    $customer->setCustomAttribute($attributeCode, $fileInfo['file']);
                }
            }
            $this->customerRepository->save($customer);

        } catch (\Exception $e) {
            $this->logger->critical($e);
            $response = [
                'error' => true,
                'message' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ];
        }

        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        if (!$response['error']) {
            $response['redirect_url'] = $this->getRedirectUrl();
        }
        $resultJson->setData($response);
        return $resultJson;
    }

    /**
     * Get Referer Url
     *
     * @return string
     */
    private function getRedirectUrl(): string
    {
        $url = $this->urlModel->getBaseUrl();
        $referer = $this->getRequest()->getParam('referer');
        if ($referer) {
            $url = $this->urlDecoder->decode($referer);
        }

        return $url;
    }

    /**
     * Move file from temporary folder to the media folder
     *
     * @param array $fileInfo
     * @throws LocalizedException
     */
    private function moveTmpFileToSuitableFolder(&$fileInfo)
    {
        $fileName = $fileInfo['file'];
        $fileProcessor = $this->fileProcessorFactory
            ->create(['entityTypeCode' => CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER]);

        $newFilePath = $fileProcessor->moveTemporaryFile($fileName);
        $fileInfo['file'] = $newFilePath;
        $fileInfo['url'] = $fileProcessor->getViewUrl(
            $newFilePath,
            'file'
        );
    }
}

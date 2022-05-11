<?php declare(strict_types=1);

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
use Psr\Log\LoggerInterface;

class Upload extends AbstractAccount implements HttpPostActionInterface
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
     * UploadFile constructor.
     * @param Context $context
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerMetadataInterface $customerMetadataService
     * @param FileProcessorFactory $fileProcessorFactory
     * @param FileUploaderFactory $fileUploaderFactory
     * @param LoggerInterface $logger
     * @param Session $session
     */
    public function __construct(
        Context $context,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        CustomerMetadataInterface $customerMetadataService,
        FileProcessorFactory $fileProcessorFactory,
        FileUploaderFactory $fileUploaderFactory,
        LoggerInterface $logger,
        Session $session
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerMetadataService = $customerMetadataService;
        $this->customerFactory = $customerFactory;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->session = $session;
        $this->logger = $logger;
        $this->fileProcessorFactory = $fileProcessorFactory;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $customer = $this->customerRepository->getById($this->session->getCustomerId());
            if (!empty($_FILES)) {
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
                    $result = $this->processError(($errorMessage));
                } else {
                    $result = $fileUploader->upload();
                    $this->moveTmpFileToSuitableFolder($result);
                    $customer->setCustomAttribute($attributeCode, $result['file']);
                    $this->customerRepository->save($customer);
                }
            } else {
                $result = $this->processError(__('No files for upload.'));
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $result = $this->processError($e->getMessage(), $e->getCode());
        }

        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result);
        return $resultJson;
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

    /**
     * Prepare result array for errors
     *
     * @param string $message
     * @param int $code
     * @return array
     */
    private function processError($message, $code = 0)
    {
        return [
            'error' => $message,
            'errorcode' => $code,
        ];
    }
}

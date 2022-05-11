<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Observer\Page;

use Magefan\CmsDisplayRules\Observer\AbstractDisplayAfter;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magefan\CmsDisplayRules\Model\PageFactory;
use Magento\Framework\App\RequestInterface;
use Magefan\CmsDisplayRules\Model\Validator;
use Magefan\CmsDisplayRules\Model\PageRepository;
use Magento\Cms\Model\PageRepository as CmsPageRepository;
use Magefan\CmsDisplayRules\Model\Config;

/**
 * Class PageDisplayAfter
 */
class DisplayAfter implements ObserverInterface
{

    /**
     * @var PageFactory
     */
    protected $factory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var string
     */
    protected $field;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @var CmsPageRepository
     */
    protected $cmsPageRepository;

    /**
     * @var Config
     */
    protected $config;

    /**
     * DisplayAfter constructor.
     * @param PageFactory $factory
     * @param RequestInterface $request
     * @param Validator $validator
     * @param PageRepository $pageRepository
     * @param CmsPageRepository $cmsPageRepository
     * @param Config $config
     * @param string $field
     */
    public function __construct(
        PageFactory $factory,
        RequestInterface $request,
        Validator $validator,
        PageRepository $pageRepository,
        CmsPageRepository $cmsPageRepository,
        Config $config,
        $field = 'page_id'
    ) {
        $this->factory = $factory;
        $this->request = $request;
        $this->validator = $validator;
        $this->field = $field;
        $this->pageRepository = $pageRepository;
        $this->cmsPageRepository = $cmsPageRepository;
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isEnabled()) {
            try {
                $cmsModel = $this->pageRepository->getById($this->request->getParam($this->field));
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return;
            }
            if ($this->validator->isRestricted($cmsModel)) {
                $controller = $observer->getControllerAction();

                $identifier = 'no-route';
                if (!empty($cmsModel->getData('another_cms'))) {
                    try {
                        $anotherPage = $this->cmsPageRepository->getById($cmsModel->getData('another_cms'));
                        if ($anotherPage->getIdentifier()) {
                            $identifier = $anotherPage->getIdentifier();
                        }
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $e->getMessage();
                    }
                }

                $controller->getResponse()->setRedirect($identifier)->sendResponse();
                $controller->getResponse()->setDispatched(false);
                \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\App\ActionFlag::class)
                    ->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);

            }
        }
    }
}

<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Observer\Block;

use Magefan\CmsDisplayRules\Observer\AbstractDisplayAfter;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magefan\CmsDisplayRules\Model\BlockRepository;
use Magefan\CmsDisplayRules\Model\Validator;
use Magento\Framework\App\RequestInterface;
use Magento\Cms\Model\BlockRepository as CmsBlockRepository;
use Magefan\CmsDisplayRules\Model\Config;

/**
 * Class BlockDisplayAfter
 */
class DisplayAfter implements ObserverInterface
{

    /**
     * @var BlockRepository
     */
    protected $blockRepository;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var CmsBlockRepository
     */
    protected $cmsBlockRepository;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $processing = [];

    /**
     * DisplayAfter constructor.
     * @param BlockRepository $blockRepository
     * @param Validator $validator
     * @param CmsBlockRepository $cmsBlockRepository
     * @param RequestInterface $request
     * @param Config $config
     */
    public function __construct(
        BlockRepository $blockRepository,
        Validator $validator,
        CmsBlockRepository $cmsBlockRepository,
        RequestInterface $request,
        Config $config
    ) {
        $this->blockRepository = $blockRepository;
        $this->validator = $validator;
        $this->cmsBlockRepository = $cmsBlockRepository;
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isEnabled()) {
            $block = $observer->getBlock();
            if ($block instanceof \Magento\Cms\Block\Block
                || $block instanceof \Magento\Cms\Block\Widget\Block
            ) {
                $blockId = $block->getId() ?: $block->getBlockId();
                if (!is_numeric($blockId)) {
                    try {
                        $blockId = $this->cmsBlockRepository->getById($blockId)->getId();
                    } catch (NoSuchEntityException $e) {
                        $blockId = 0;
                    }
                }
                $blockId = (int)$blockId;
                if (!$blockId) {
                    return;
                }

                if (!empty($this->processing[$blockId])) {
                    /* Prevent Loop */
                    $this->processing[$blockId] = false;
                    $observer->getTransport()->setHtml('');
                    return;
                }
                $this->processing[$blockId] = true;

                try {
                    $cmsModel = $this->blockRepository->getById($blockId);
                } catch (NoSuchEntityException $e) {
                    $cmsModel = false;
                }
                if ($cmsModel && $cmsModel->getId()) {
                    $ajax = $this->request->isXmlHttpRequest();
                    if ($this->validator->hasDynamicConditions($cmsModel) && !$ajax) {
                        $divId = 'mfcmsdr-' . $blockId;
                        if (empty($cmsModel->getSecret())) {
                            $cmsModel->generateSecret();
                        }
                        $html = '<div 
                                    class="'. $block->escapeHtml($divId) .
                            '" id="' . $block->escapeHtml($divId) .
                            '" data-blockid="' .  $blockId .
                            '" data-secret="'. $cmsModel->getData('secret') .
                            '" style="display:none;">
                                    
                         </div>';

                        $observer->getTransport()->setHtml($html);
                    } else {
                        if ($this->validator->isRestricted($cmsModel)) {
                            $html = '';
                            $anotherCmsId = $cmsModel->getData('another_cms');
                            if ($anotherCmsId) {
                                $blockClass = str_replace('\Interceptor', '', get_class($block));
                                $html = $block->getLayout()->createBlock($blockClass)
                                    ->setData($block->getData())
                                    ->setTemplate($block->getTemplate())
                                    ->setBlockId($anotherCmsId)
                                    ->setId($anotherCmsId)
                                    ->toHtml();
                            }
                            $observer->getTransport()->setHtml(
                                '<!-- CMS Block ' . $blockId . ' -> ' . $anotherCmsId . ' -->' .
                                $html
                            );
                        }
                    }
                }
                $this->processing[$blockId] = false;
            }
        }
    }
}

<?php

/**
 * Retailplace_Theme
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Theme\Plugin;

use Closure;
use Magento\Cms\Model\BlockRepository as CmsBlockRepository;
use Magefan\CmsDisplayRules\Model\BlockRepository;
use Magefan\CmsDisplayRules\Model\Config;
use Magefan\CmsDisplayRules\Model\Validator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;

class CmsBlock
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
     * Validate if the block should be displayed
     *
     * @param AbstractBlock $subject
     * @param Closure $proceed
     * @return string
     */
    public function aroundToHtml(
        AbstractBlock $subject,
        Closure $proceed
    ): ?string {
        if (!$this->config->isEnabled()) {
            return $proceed();
        }

        $blockId = $subject->getId() ?: $subject->getBlockId();
        if (!is_numeric($blockId)) {
            try {
                $blockId = $this->cmsBlockRepository->getById($blockId)->getId();
            } catch (NoSuchEntityException $e) {
                $blockId = 0;
            }
        }

        if (!$blockId) {
            return $proceed();
        }

        if (!empty($this->processing[$blockId])) {
            /* Prevent Loop */
            $this->processing[$blockId] = false;
            return '';
        }
        $this->processing[$blockId] = true;

        try {
            $cmsModel = $this->blockRepository->getById($blockId);
        } catch (NoSuchEntityException $e) {
            $cmsModel = false;
        }

        if (!$cmsModel || !$cmsModel->getId()) {
            return $proceed();
        }

        $html = '';
        $ajax = $this->request->isXmlHttpRequest();
        if ($this->validator->hasDynamicConditions($cmsModel) && !$ajax) {
            $divId = 'mfcmsdr-' . $blockId;
            if (empty($cmsModel->getSecret())) {
                $cmsModel->generateSecret();
            }
            $html = '<div class="' . $subject->escapeHtml($divId) .
                '" id="' . $subject->escapeHtml($divId) .
                '" data-blockid="' . $blockId .
                '" data-secret="' . $cmsModel->getData('secret') .
                '" style="display:none;"></div>';
        } else {
            if ($this->validator->isRestricted($cmsModel)) {
                $anotherCmsId = $cmsModel->getData('another_cms');
                if ($anotherCmsId) {
                    $blockClass = str_replace('\Interceptor', '', get_class($subject));
                    $html = $subject->getLayout()->createBlock($blockClass)
                        ->setData($subject->getData())
                        ->setTemplate($subject->getTemplate())
                        ->setBlockId($anotherCmsId)
                        ->setId($anotherCmsId)
                        ->toHtml();
                }
            } else {
                $html = $proceed();
            }
        }

        $this->processing[$blockId] = false;
        return !empty($html) ? $html : "";
    }
}

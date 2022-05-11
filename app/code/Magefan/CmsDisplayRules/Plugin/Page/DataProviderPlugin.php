<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Plugin\Page;

use Magento\Framework\Registry;
use Magento\Framework\App\RequestInterface;
use Magefan\CmsDisplayRules\Model\ResourceModel\Page\Collection;
use Magento\Cms\Model\Page\DataProvider;
use Magefan\CmsDisplayRules\Model\PageRepository;

/**
 * Class DataProviderPlugin plugin
 */
class DataProviderPlugin
{

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * DataProviderPlugin constructor.
     * @param RequestInterface $request
     * @param Collection $collection
     */
    public function __construct(
        RequestInterface $request,
        PageRepository $pageRepository
    ) {
        $this->request = $request;
        $this->pageRepository = $pageRepository;
    }

    /**
     * @param DataProvider $subject
     * @param $proceed
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundGetData(DataProvider $subject, $proceed)
    {
        $conditions = [
            'group_id',
            'start_date',
            'finish_date',
            'time_from',
            'time_to',
            'days_of_week',
            'conditions_serialized',
            'another_cms'
        ];

        $result = $proceed();
        $pageId = $this->request->getParam('page_id');
        if ($pageId) {
            $pageRule = $this->pageRepository->getById($pageId);
            if ($pageRule) {
                $data[] = $pageRule->getData();
                if ($data) {
                    foreach ($conditions as $condition) {
                        if (array_key_exists($condition, $data[0]) && ($data[0][$condition] || 0 === $data[0][$condition] || '0' === $data[0][$condition] )) {
                            if ($condition == 'group_id' || $condition== 'days_of_week') {
                                $result[$pageId]['magefan_cms_display_rules['. $condition . ']'] = explode(
                                    ',',
                                    $data[0][$condition]
                                );
                            } else {
                                $result[$pageId]['magefan_cms_display_rules['. $condition . ']'] = $data[0][$condition];
                            }

                        }
                    }
                }
            }
        }
        return $result;
    }
}

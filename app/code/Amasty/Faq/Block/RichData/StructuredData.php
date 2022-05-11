<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */

declare(strict_types=1);

namespace Amasty\Faq\Block\RichData;

use Amasty\Faq\Api\Data\QuestionInterface;
use Amasty\Faq\Block\AbstractBlock;
use Amasty\Faq\Model\ConfigProvider;
use Magento\Framework\View\Element\Template\Context;

class StructuredData extends AbstractBlock
{
    const BLOCK_NAME = 'amasty_faq_structureddata';
    const FAQ_PAGE = 'FAQPage';
    const QA_PAGE = 'QAPage';

    /**
     * @var string
     */
    protected $_template = 'Amasty_Faq::structured.phtml';

    /**
     * @var null|bool
     */
    private $canonicalUrlEnabled = null;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
    }

    /**
     * @return array
     */
    public function getStructuredData(): array
    {
        $questions = $this->getQuestions();
        $pageType = $this->getData('pageType');
        if (is_array($questions)) {
            $items = [];
            foreach ($questions as $question) {
                if (!empty($question->prepareShortAnswer())) {
                    $items[] = [
                        '@type' => 'Question',
                        'position' => $question->getPosition(),
                        'name' => $question->getTitle(),
                        'text' => $question->getTitle(),
                        'author' => $question->getName() ? $question->getName() : __('Guest'), // @todo: BTS-603
                        'dateCreated' => $question->getCreatedAt(),
                        'answerCount' => 1,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $question->prepareShortAnswer(),
                            'dateCreated' => $question->getUpdatedAt(),
                            'author' => __('Admin'),
                            'url' => $this->getQuestionUrl($question),
                            'upvoteCount' => $question->getPositiveRating(),
                            'downvoteCount' => $question->getNegativeRating()
                        ]
                    ];
                }
            }

            if (!empty($items)) {
                return [
                    '@context' => 'http://schema.org',
                    '@type' => $pageType,
                    'speakable' => [
                        '@type' => 'SpeakableSpecification',
                        'xPath' => ['/html/head/title']
                    ],
                    'mainEntity' => $items
                ];
            }
        }

        return [];
    }

    /**
     * @return array
     */
    public function getQuestions(): array
    {
        $questions = [];
        if ($this->getData('questions')) {
            $questions = $this->getData('questions');
        } elseif (method_exists($this->getParentBlock(), 'getStructuredDataQuestions')) {
            $questions = $this->getParentBlock()->getStructuredDataQuestions();
        }

        return $questions;
    }

    /**
     * @return bool
     */
    private function isCanonicalUrlEnabled(): bool
    {
        if ($this->canonicalUrlEnabled === null) {
            $this->canonicalUrlEnabled = $this->configProvider->isCanonicalUrlEnabled();
        }

        return $this->canonicalUrlEnabled;
    }

    /**
     * @param QuestionInterface $question
     *
     * @return string
     */
    private function getQuestionUrl(QuestionInterface $question): string
    {
        $questionUrlKey = $question->getUrlKey();
        if ($this->isCanonicalUrlEnabled() && $question->getCanonicalUrl()) {
            $questionUrlKey = $question->getCanonicalUrl();
        }

        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlKey() . '/' . $questionUrlKey
        );
    }
}

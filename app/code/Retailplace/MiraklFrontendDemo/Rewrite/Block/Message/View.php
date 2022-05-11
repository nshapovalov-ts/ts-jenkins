<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Block\Message;

use DateTimeInterface;
use Exception;
use Magento\Framework\Phrase;
use Mirakl\FrontendDemo\Block\Message\View as MessageView;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadAttachment;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadEntity;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadMessage;
use Magento\Framework\Exception\LocalizedException;
use IntlDateFormatter;
use DateTimeZone;
use Mirakl\MMP\Common\Domain\Collection\Message\Thread\ThreadMessageCollection;

class View extends MessageView
{
    /**
     * @var Phrase
     */
    private $pageTitle;

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->getPageTitle();
    }

    /**
     * Set Page Title
     * @return Phrase
     */
    public function getPageTitle()
    {
        if (!empty($this->pageTitle)) {
            return $this->pageTitle;
        }

        $thread = $this->getThread();
        $helper = $this->getMessageHelper();
        $entityRef = "";
        foreach ($thread->getEntities() as $entity) {
            $link = $this->getEntityUrl($entity);
            if ($link) {
                $entityRef = sprintf('<a href="%s">%s</a>', $link, $this->escapeHtmlAttr($entity->getLabel()));
            } else {
                $entityRef = $this->escapeHtmlAttr($entity->getLabel());
            }
        }

        $entityName = $helper->getEntityName($entity);
        $title = "";
        if ($entityName === "Product") {
            $this->pageTitle = __($entityRef);
        } else {
            $this->pageTitle = __(
                '%1 #%2',
                '<span class="customer-messages-type">' .
                $this->escapeHtmlAttr(__($entityName)) .
                '</span>',
                $entityRef
            );
        }

        $this->pageConfig->getTitle()->set(strip_tags($this->pageTitle->render()));
        return $this->pageTitle;
    }

    /**
     * Get Attachment Url
     *
     * @param ThreadAttachment $attachment
     * @return  string
     * @throws LocalizedException
     */
    public function getAttachmentUrl(ThreadAttachment $attachment): string
    {
        $thread = $this->getThread();

        return $this->getUrl('marketplace/message/attachment', [
            'id'       => $attachment->getId(),
            'thread'   => $thread->getId(),
            'form_key' => $this->formKey->getFormKey()
        ]);
    }

    /**
     * Get Entity Url
     *
     * @param ThreadEntity $entity
     * @return  string
     */
    public function getEntityUrl(ThreadEntity $entity): string
    {
        if (in_array($entity->getType(), ['MMP_OFFER', 'MPS_OFFER'])) {
            return '';
        }

        return parent::getEntityUrl($entity);
    }

    /**
     * Get Recipient Names
     *
     * @param ThreadMessage $message
     * @return  array
     */
    public function getRecipientNames(ThreadMessage $message): array
    {
        $typeFrom = $message->getFrom()->getType();
        $isFromOperator = $typeFrom === "OPERATOR";
        $isFromClient = $typeFrom === "CUSTOMER";
        $isFromSeller = $typeFrom === "SHOP";

        $recipients = [];
        foreach ($message->getTo()->getItems() as $recipient) {
            $typeTo = $recipient->getType();
            $recipients[$typeTo] = $typeTo;
        }

        $isToOperator = array_key_exists("OPERATOR", $recipients);
        $isToClient = array_key_exists("CUSTOMER", $recipients);
        $isToSeller = array_key_exists("SHOP", $recipients);

        $isViewTradesquareSupport = false;

        if ($isFromOperator) { //если оператор
            //recipient client or client and seller
            $isViewTradesquareSupport = $isToClient || $isToClient && $isToSeller;
        }

        $names = [];

        $message = $message->toArray();

        if (!empty($message['to'])) {
            foreach ($message['to'] as $recipient) {
                if (!empty($recipient['display_name'])) {
                    if (!$isViewTradesquareSupport && $recipient['type'] === "OPERATOR") {
                        continue;
                    }
                    $names[] = __($recipient['display_name'])->render();
                }
            }
        }

        return $names;
    }

    /**
     * Retrieve formatting date
     *
     * @param null|string|DateTimeInterface $date
     * @param int $format
     * @param bool $showTime
     * @param null|string $timezone
     * @param string $pattern
     * @return string
     * @throws Exception
     */
    public function formatDateTime(
        $date = null,
        $format = IntlDateFormatter::SHORT,
        bool $showTime = false,
        $timezone = null,
        string $pattern = "dd/MM/Y, hh:mm"
    ): string {
        $date = $date instanceof DateTimeInterface ? $date : new \DateTime($date);
        return $this->_localeDate->formatDateTime(
            $date,
            $format,
            $showTime ? $format : IntlDateFormatter::NONE,
            null,
            $timezone,
            $pattern
        );
    }

    /**
     * Get Messages Grouped By Days
     *
     * @param ThreadMessageCollection $messages
     * @return array
     */
    public function getMessagesGroupedByDays(ThreadMessageCollection $messages): array
    {
        $messagesGroup = [];
        $newMessages = [];
        $helper = $this->getMessageHelper();

        $messagesArray = [];
        foreach ($messages as $message) {
            $messagesArray[] = $message;
        }
        $messagesArray = array_reverse($messagesArray);

        foreach ($messagesArray as $messageKey => $message) {
            $messageDate = $helper->getMiraklDate($message->getDateCreated()->format(DateTimeInterface::ISO8601));
            $currentDate = new \DateTime();
            $currentDate->setTimezone(new DateTimeZone('GMT'));
            $dDiff = $messageDate->diff($currentDate);
            $diffDay = $dDiff->format('%a'); // use for point out relation: smaller/greater
            $messagesGroup[$messageKey] = $diffDay;
        }

        foreach ($messagesArray as $messageKey => $message) {
            $groupId = $messagesGroup[$messageKey];
            $newMessages[$groupId][$messageKey] = $message;
        }

        return $newMessages;
    }
}

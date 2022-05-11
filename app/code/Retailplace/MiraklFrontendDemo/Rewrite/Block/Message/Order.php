<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Block\Message;

use Mirakl\FrontendDemo\Block\Message\Order as MessageOrder;
use Mirakl\FrontendDemo\Block\Message\FormOrder;
use Mirakl\FrontendDemo\Block\Message\FormNew;
use Mirakl\FrontendDemo\Block\Message\FormReply;
use Mirakl\FrontendDemo\Block\Message\Index;
use Mirakl\FrontendDemo\Block\Message\View;

/**
 * Class Order
 */
class Order extends MessageOrder
{

    /**
     * {@inheritdoc}
     */
    public function _construct()
    {
        $threads = $this->getThreads();
        $nbThreads = $threads->getCollection()->count();

        if ($nbThreads == 0) {
            $this->tabChildren[] = $this->addBlock('marketplace.message.form.order', FormOrder::class);
        } else {
            if ($nbThreads == 1) {
                $thread = $this->getThread($threads->getCollection()->first())->toArray();
                $this->tabTitle = $thread['topic']['value'] ?? '';
                $this->tabChildren[] = $this->addBlock('marketplace.message.view', View::class);
                $this->addBlock('marketplace.message.form.reply', FormReply::class);
            } else {
                $this->tabChildren[] = $this->addBlock('marketplace.message.form.new', FormNew::class);
                $this->tabChildren[] = $this->addBlock('marketplace.message.index', Index::class);
            }
        }
    }
}

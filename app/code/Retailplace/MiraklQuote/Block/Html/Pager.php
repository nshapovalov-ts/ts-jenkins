<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Block\Html;

use Magento\Theme\Block\Html\Pager as MagentoPager;

/**
 * Class Pager
 */
class Pager extends MagentoPager
{
    protected $_template = 'Retailplace_MiraklQuote::html/pager.phtml';
}

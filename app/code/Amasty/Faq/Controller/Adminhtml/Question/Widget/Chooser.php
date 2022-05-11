<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


declare(strict_types=1);

namespace Amasty\Faq\Controller\Adminhtml\Question\Widget;

use Amasty\Faq\Block\Adminhtml\Question\Widget\Chooser as QuestionChooser;
use Amasty\Faq\Controller\Adminhtml\AbstractWidgetChooserController;

class Chooser extends AbstractWidgetChooserController
{
    const ADMIN_RESOURCE = 'Amasty_Faq::question';

    /**
     * @return string
     */
    public function getChooserGridClass()
    {
        return QuestionChooser::class;
    }
}

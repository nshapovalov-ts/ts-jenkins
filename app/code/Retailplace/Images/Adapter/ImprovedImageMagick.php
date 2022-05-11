<?php

/**
 * Retailplace_Images
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Images\Adapter;

use ImagickException;
use Magento\Framework\Image\Adapter\ImageMagick;

/**
 * Class ImprovedImageMagick
 */
class ImprovedImageMagick extends ImageMagick
{
    /**
     * Resize with keeping the correct image rotation
     *
     * @param null $frameWidth
     * @param null $frameHeight
     *
     * @throws ImagickException
     */
    public function resize($frameWidth = null, $frameHeight = null)
    {
        $imageOrientation = $this->_imageHandler->getImageProperty('exif:Orientation');
        switch ($imageOrientation) {
            case 3:
                $this->rotate(180);
                break;

            case 6:
                $this->rotate(-90);
                break;

            case 8:
                $this->rotate(90);
                break;
        }
        parent::resize($frameWidth, $frameHeight);
    }
}

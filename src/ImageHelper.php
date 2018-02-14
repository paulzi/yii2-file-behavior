<?php
namespace paulzi\fileBehavior;

use Yii;
use yii\base\InvalidParamException;
use yii\imagine\BaseImage;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\Palette\RGB;
use Imagine\Image\ImageInterface;

class ImageHelper extends BaseImage
{
    const MODE_NO_ASPECT = false;
    const MODE_IN        = 0;
    const MODE_OUT       = 1;


    /**
     * @param string $filename the image file path or path alias.
     * @param integer $width the width in pixels to create the thumbnail
     * @param integer $height the height in pixels to create the thumbnail
     * @param array $options
     * @return ImageInterface
     */
    public static function resize($filename, $width, $height, $options = [])
    {
        $mode      = isset($options['mode'])      ? $options['mode']      : self::MODE_IN;
        $filter    = isset($options['filter'])    ? $options['filter']    : ImageInterface::FILTER_UNDEFINED;
        $blur      = isset($options['blur'])      ? $options['blur']      : 1;
        $crop      = isset($options['crop'])      ? $options['crop']      : true;
        $add       = isset($options['add'])       ? $options['add']       : false;
        $downscale = isset($options['downscale']) ? $options['downscale'] : true;
        $noscale   = isset($options['noscale'])   ? $options['noscale']   : false;
        $upscale   = isset($options['upscale'])   ? $options['upscale']   : false;
        $alignX    = isset($options['alignX'])    ? $options['alignX']    : 0.5;
        $alignY    = isset($options['alignY'])    ? $options['alignY']    : 0.5;

        $img  = static::getImagine()->open(Yii::getAlias($filename));
        $size = $img->getSize();
        $w    = $size->getWidth();
        $h    = $size->getHeight();
        $sx   = $width  ? $width  / $w : false;
        $sy   = $height ? $height / $h : false;

        if ($mode !== false && $sx && $sy) {
            $sx = $sy = min($sx, $sy) + abs($sy - $sx) * $mode;
        } else {
            $sx = $sx !== false ? $sx : $sy;
            $sy = $sy !== false ? $sy : $sx;
        }

        if ($sx === false || $sy === false) {
            throw new InvalidParamException('Incompatible width, height and mode');
        }

        switch (true) {
            case $downscale && $sx < 1   && $sy < 1:
            case $upscale   && $sx > 1   && $sy > 1:
            case $noscale   && $sx === 1 && $sy === 1:
                $w = (int)round($w * $sx);
                $h = (int)round($h * $sy);
                if ($img instanceof \Imagine\Imagick\Image) {
                    static $map = array(
                        ImageInterface::FILTER_UNDEFINED => \Imagick::FILTER_UNDEFINED,
                        ImageInterface::FILTER_BESSEL    => \Imagick::FILTER_BESSEL,
                        ImageInterface::FILTER_BLACKMAN  => \Imagick::FILTER_BLACKMAN,
                        ImageInterface::FILTER_BOX       => \Imagick::FILTER_BOX,
                        ImageInterface::FILTER_CATROM    => \Imagick::FILTER_CATROM,
                        ImageInterface::FILTER_CUBIC     => \Imagick::FILTER_CUBIC,
                        ImageInterface::FILTER_GAUSSIAN  => \Imagick::FILTER_GAUSSIAN,
                        ImageInterface::FILTER_HANNING   => \Imagick::FILTER_HANNING,
                        ImageInterface::FILTER_HAMMING   => \Imagick::FILTER_HAMMING,
                        ImageInterface::FILTER_HERMITE   => \Imagick::FILTER_HERMITE,
                        ImageInterface::FILTER_LANCZOS   => \Imagick::FILTER_LANCZOS,
                        ImageInterface::FILTER_MITCHELL  => \Imagick::FILTER_MITCHELL,
                        ImageInterface::FILTER_POINT     => \Imagick::FILTER_POINT,
                        ImageInterface::FILTER_QUADRATIC => \Imagick::FILTER_QUADRATIC,
                        ImageInterface::FILTER_SINC      => \Imagick::FILTER_SINC,
                        ImageInterface::FILTER_TRIANGLE  => \Imagick::FILTER_TRIANGLE
                    );

                    $img->getImagick()->resizeImage($w, $h, $map[$filter], $blur);
                } else {
                    $img->resize(new Box($w, $h), $filter);
                }
                break;
        }

        if ($width && $height && ($crop || $add)) {
            $x = intval(($w - $width)  * $alignX);
            $y = intval(($h - $height) * $alignY);
            if ($add && ($x < 0 || $y < 0)) {
                $palette = new RGB();
                $color = $palette->color('#FFF', 100);
                $thumb = static::getImagine()->create(new Box(max($width, $w), max($height, $h)), $color);
                $thumb->paste($img, new Point(max(0, -$x), max(0, -$y)));
                $img = $thumb;
                $x = max($x, 0);
                $y = max($y, 0);
            }
            if ($crop && ($x > 0 || $y > 0)) {
                $img->crop(new Point(max(0, $x), max(0, $y)), new Box($width, $height));
            }
        }

        return $img;
    }
}
<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Img\Type;

/**
* Этот класс задает тип масштабирования картинки.
* 
* Crop Top Y
*/
class Cty extends AbstractType
{
    public 
        $background = array(255,255,255);
        
    
    public function resizeImage(\RS\Img\File $srcImage, $dstImageFileName, $final_width, $final_height, $quality = 90)
    {
        //Исходные размеры
        $w_image = $srcImage->width;
        $h_image = $srcImage->height;
        if ($w_image == 0) return false;
        
        if ($w_image < $final_width) $final_width = $w_image;
        if ($h_image < $final_height) $final_height = $h_image;
        
        $width = $final_width;
        $height = $final_height;
        if ($h_image != $final_height) {
            $height = round($final_width * $h_image/$w_image);
        }
        
        $newImage = new \RS\Img\Create($final_width, $final_height, $srcImage->type);
       	imagecopyresampled ($newImage->handler, $srcImage->image_handler, 0, 0, 0, 0, $width, $height, $w_image, $h_image);
        $newImage->setQuality($quality)->save($dstImageFileName);
        return true;
    }
    
}

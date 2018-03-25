<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Img\Type;

/**
* Абстрактный класс для Ресайзеров изображений
*/
abstract class AbstractType {
    
    abstract public function resizeImage(\RS\Img\File $srcImage, $dstImageFileName, $width, $height, $quality = 90);

}


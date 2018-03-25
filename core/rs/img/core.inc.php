<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Img;

/**
* Класс, отвечающий за отдачу картинок соответсвующего типа
* Сообщаем ему, папку исходников. папку, в которую будут рендериться уменьшенные фото.
*/
class Core
{
    const
        WM_POSX_LEFT = 'left',
        WM_POSX_CENTER = 'center',
        WM_POSX_RIGHT = 'right',
        
        WM_POSY_TOP = 'top',
        WM_POSY_MIDDLE = 'middle',
        WM_POSY_BOTTOM = 'bottom';
    
    protected static
        $_cache = array();
    
    protected
        $base,
        $watermark = false,
        $srcFolder,
        $dstFolder,
        
        $quality;
    
    function __construct($base, $srcFolder, $dstFolder)
    {
        $this->base = $base;
        $this->srcFolder = $srcFolder;
        $this->dstFolder = $dstFolder;
        
        $sysConfig = \RS\Config\Loader::get('\Main\Config\File');
        if (!empty($sysConfig['watermark'])) {
            $this->setWatermark($sysConfig->getProp('watermark')->getFullPath(), 
                $sysConfig['wmark_min_width'], 
                $sysConfig['wmark_min_height'], 
                $sysConfig['wmark_pos_x'], 
                $sysConfig['wmark_pos_y'],
                $sysConfig['wmark_opacity']
                );
        }
        
        $quality = $sysConfig['image_quality'] > 100 ? 100 : $sysConfig['image_quality'];
        $quality = $quality < 0 ? 0 : $quality;
        
        $this->setQuality($quality);
    }
    
    /**
    * Устанавливать водяной знак на создаваемые изображения
    * 
    * @param string $file полный путь к PNG файлу водяного знака
    * @param integer $minWidth минимальная ширина изображения, на которое будет наложен водяной знак
    * @param integer $minHeight минимальная высота изображения, на которое будет наложен водяной знак
    * @param string $posX положение водяного знака по горизонтали (см. константы WM_POSX_...)
    * @param string $posY положение водяного знака по вертикали (см. константы WM_POSY_...)
    */
    function setWatermark($file, $minWidth = 0, $minHeight = 0, $posX = self::WM_POSX_CENTER, $posY = self::WM_POSY_MIDDLE, $opacity = 100)
    {
        $this->watermark = array(
            'file' => $file,
            'minWidth' => $minWidth,
            'minHeight' => $minHeight,
            'posX' => $posX,
            'posY' => $posY,
            'opacity' => $opacity
        );
    }
    
    /**
    * Отключает установку водяного знака на изображения
    */
    function disableWatermark()
    {
        $this->watermark = false;
    }
    
    /**
    * Устанавливает качество итогового JPG изображения
    * 
    * @param integer $n от 0 до 100
    */
    function setQuality($n)
    {
        $this->quality = $n;
    }    
    
    /**
    * Отправляет файл в output
    * 
    * @param string $origFileName - имя оригинального файла
    * @param int $width - ширина
    * @param int $height - высота
    * @param string ("xy"|"axy"|"cxy") $type - тип
    * @param string $hash - ключ
    */
    function toOutput($origFileName, $width, $height, $type, $hash)
    {
        if (!$this->checkOpenKey($origFileName, $width, $height, $type, $hash)) {
            throw new Exception(t('Неверная подпись ссылки'), Exception::IMG_BAD_LINK_SIGN);
        }
        
        $url = $this->buildImage($origFileName, $width, $height, $type);        
        $size = getimagesize($this->base.$url);
        header("Content-type: {$size['mime']}");
        
        $img_content = readfile($this->base.$url);
    }
    
    /**
    * Создает изображение, согласно параметрам
    * 
    * @param string $origFileName - имя оригинального файла
    * @param int $width - ширина
    * @param int $height - высота
    * @param string ("xy"|"axy"|"cxy") $type - тип
    * @param string $urltype - тип по которому формировать url изображения. Если null, то = $type
    * @return string возвращает относительную ссылку на изображение
    */
    function buildImage($origFileName, $width, $height, $type, $urltype = null)
    {
        if ($urltype === null) $urltype = $type;
        $dst = $this->getImageUrl($origFileName, $width, $height, $urltype);
        if (file_exists($this->base.$dst))
        {
            return $dst;
        }
        $file = new File($this->base.$this->srcFolder.'/'.$origFileName);
        $this->checkFolder(\Setup::$ROOT.$dst);
        
        $type{0} = strtoupper($type{0}); //Первая буква - заглавная
        $imgResizerClass = '\RS\Img\Type\\'.$type;
        $imgResizer = new $imgResizerClass();
        
        if ($imgResizer->resizeImage($file, $this->base.$dst, $width, $height, $this->quality))
        {
            if ($this->watermark) {
                $this->putWatermark( new \RS\Img\File($this->base.$dst) );
            }
            //Вызовем событие после создания фото
            \RS\Event\Manager::fire("img.core.afterbuild", array(
                'file' => $dst
            ));
            return $dst;
        } else {
            return $imgSize->no_photo_url;
        }
        unset($file);
    }
    
    /**
    * Возвращает полный путь к оригиналу изображения
    */
    public function getOriginalFilename($origFileName)
    {
        return $this->base.$this->srcFolder.'/'.$origFileName;
    }
    
    /**
    * Исправленная функция imagecopymerge с поддержкой альфаканала у watermark
    */
    protected function imageCopyMergeAlpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        $cut = imagecreatetruecolor($src_w, $src_h);
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
    }     
    
    /**
    * Устанавливает водяной знак на изображение
    * 
    * @param CImg_File $source
    * @param mixed $offset отступ от краев
    */
    public function putWatermark(File $source, $offset = 5)
    {
        if ($source->width < $this->watermark['minWidth'] 
                || $source->height < $this->watermark['minHeight']) return false;
        
        $r = imagecreatefrompng( $this->watermark['file'] );
        $x = imagesx($r);
        $y = imagesy($r);

        switch($this->watermark['posX']) {
            case self::WM_POSX_LEFT: $xDest = $offset; break;
            case self::WM_POSX_CENTER: $xDest = round($source->width/2 - ($x/2)); break;
            case self::WM_POSX_RIGHT: $xDest = $source->width - ($x + $offset);
        }
            
        switch($this->watermark['posY']) {
            case self::WM_POSY_TOP : $yDest = $offset; break;
            case self::WM_POSY_MIDDLE: $yDest = round($source->height/2 - ($y/2)); break;
            case self::WM_POSY_BOTTOM: $yDest = $source->height - ($y + $offset); break;
        }
        
        imageAlphaBlending($source->image_handler, true);
        imageAlphaBlending($r, true);
        imagesavealpha($source->image_handler, true);
        imagesavealpha($r, true);
        if ($this->watermark['opacity']<100) {
            $this->imageCopyMergeAlpha($source->image_handler,$r,$xDest,$yDest,0,0,$x,$y, $this->watermark['opacity']);
        } else {
            imagecopyresampled($source->image_handler,$r,$xDest,$yDest,0,0,$x,$y,$x,$y);
        }
        imagedestroy($r);
        $result = new Create(null, null, null, $source);
        $result->setQuality($this->quality);
        $result->save($source->filename, false);
    }
    
    /**
    * Возвращает ссылку на изображение
    * 
    * @param string $origFileName - имя оригинального файла
    * @param int $width - ширина
    * @param int $height - высота
    * @param string ("xy"|"axy"|"cxy") $type - тип
    * @param bool $absolute - если задано true, то будет возвращен абсолютный путь, иначе относительный
    * @return string 
    */
    public function getImageUrl($origFileName, $width, $height, $imgType = 'xy', $absolute = false)
    {
        $fname = $origFileName;
        $ext = 'jpg';
        if (preg_match('/(.+)\.(.*)/u', $origFileName, $match)) {
            $fname = $match[1];
            $ext = $match[2];
        }

        $imgType = strtolower($imgType);

        //Чтобы пользователь не смог сам сконструировать ссылку на картинку, подписываем часть URL используя секретный ключ.
        //Такой механизм позволит не создавать на сервере список возможных размеров картинок и не даст возможность перебирать все картинки.
        
        $openKey = $this->generateOpenKey($fname, $width, $height, $imgType);

        //Формируем ссылку на изображение
        $url =  strtolower($this->dstFolder.'/'.$imgType.'_'.$width.'x'.$height.'/'.$fname.'_'.$openKey.'.'.$ext);

        return $absolute ?  \RS\Site\Manager::getSite()->getAbsoluteUrl($url) : $url;
    }
    
    /**
    * Проверяет соответствие ключа параметрам изображения
    * 
    * @return bool возвращает true - если ключ соответствует, иначе false
    */
    public function checkOpenKey($fname, $width, $height, $type, $openKey)
    {
        list($name, $ext) = \RS\File\Tools::parseFileName($fname);
        
        $rightKey = $this->generateOpenKey($name, $width, $height, $type);
        return (!strcmp($rightKey, $openKey));
    }
    
    /**
    * Возвращает ключ для набора параметров изображения
    * 
    * @return string
    */
    protected function generateOpenKey($fname, $width, $height, $type)
    {
        return sprintf('%x', crc32( $fname . $width . $height . $type . \Setup::$SECRET_KEY));
    }
    
    /**
    * Проверяет наличие необходимой директории для файла,
    * в случае отсутствия создает
    */
    protected function checkFolder($dstFilename)
    {
        $dstFilename = dirname($dstFilename);
        return file_exists($dstFilename) ? true : \RS\File\Tools::makePath($dstFilename);
    }
    
    /**
    * Удаляет оригинал изображения и все имеющиеся превью
    * 
    * @param string $originalName
    * @param boolean $excludeOriginal - если true, то не удалять оригинал
    */
    function removeFile($origFileName, $excludeOriginal = false)
    {
        if (!$excludeOriginal) {
            @unlink( $this->base.$this->srcFolder.'/'.$origFileName );
        }
        $types = $this->getPreviewTypes();
        foreach($types as $type) {
            $relative_path = $this->getImageUrl($origFileName, $type['width'], $type['height'], $type['scale']);
            @unlink( $this->base.$relative_path );
        }
        return true;
    }
    
    /**
    * Возвращает все типы уже созданных preview
    */
    protected function getPreviewTypes()
    {
        $dir = $this->base.$this->dstFolder.'/';
        if (!isset(self::$_cache[$dir])) {
            $types = array();
            if (is_dir($dir)) {
                if ($dh = opendir($dir)) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file=='.' || $file=='..' || $file =='.svn') continue;
                        if (is_dir($dir.$file) && preg_match('/^(.+?)_(\d+)x(\d+)$/', $file, $match)) {
                            $types[] = array(
                                'scale' => $match[1],
                                'width' => $match[2],
                                'height' => $match[3]
                            );
                        }
                    }
                    closedir($dh);
                }
            }
            self::$_cache[$dir] = $types;
        }
        return self::$_cache[$dir];
    }
    
    /**
    * Переворачивает изображение на заданное количество градусов
    * 
    * @param mixed $origFileName имя оригинального файла
    * @param mixed $angle угол поворота
    * @param mixed $background фон заливки
    */
    function rotate($origFileName, $angle = -90, $background = 'FFFFFF')
    {
        $original_file = new File($this->getOriginalFilename($origFileName));
        $fillcolor = imagecolorallocate($original_file->image_handler, '0x'.substr($background,0,2), '0x'.substr($background,2,2), '0x'.substr($background,4,2));
        $rotated = imagerotate($original_file->image_handler, (float)$angle, $fillcolor);
        imagejpeg($rotated, $original_file->filename, 100);
        $this->removeFile($origFileName, true);
    }
}


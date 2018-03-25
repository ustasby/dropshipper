<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Img;

/**
* Класс объектов - "файл изображения". Содержит сведения об одном изображении
*/
class File 
{
    public 
        $filename,
        $width,
        $height,
        $type,
        $bits,
        $channels,
        $mime,
        $image_handler;
    
    /**
    * Конструктор. 
    * 
    * @param string $filename - путь к файлу с изображением
    * @return File
    */
    function __construct($filename)
    {
        $this->filename = $filename;
        $this->load();
    }
    
    /**
    * Загружает информацию об изображении
    * 
    * @return void
    */
    function load()
    {
        if (!file_exists($this->filename)) throw new Exception(t("Не найдена картинка %0", array($this->filename)), Exception::IMG_FILE_NOT_FOUND);
        $image_info = getimagesize($this->filename);
        
        $this->width = $image_info[0];
        $this->height = $image_info[1];
        $this->type = $image_info[2];
        $this->bits = @$image_info['bits'];
        $this->mime = $image_info['mime'];
        $this->channels = @$image_info['channels'];
        $this->getImageHandler();
    }
    
    /**
    * Возвращает указать на ресурс изображения
    * @return mixed
    */
    function getImageHandler()
    {
        if ($this->mime == "image/jpeg") $this->image_handler = imagecreatefromjpeg($this->filename);
        else if ($this->mime == "image/gif") $this->image_handler = imagecreatefromgif($this->filename);
        else if ($this->mime == "image/png") $this->image_handler = imagecreatefrompng($this->filename);
    }
    
    /**
    * Уничтожает указатель на ресурс изображения
    * @return void
    */
    function __destruct()
    {
        imagedestroy($this->image_handler);        
    }
}


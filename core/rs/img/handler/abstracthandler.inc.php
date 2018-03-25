<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Img\Handler;

/**
* Базовый абстрактный класс для всех обработчиков изображений
*/
abstract class AbstractHandler implements \RS\Controller\IController
{
    protected
        $width,
        $height,
        $scale,
        $pic_id,
        $hash,
        
        $url,
        //Должны быть заданы у наследника
        $srcFolder,  //папка с оригиналами изображений (относительный путь от корня)
        $dstFolder;  //папка с измененными изображениями(относительный путь от корня)


    /**
    * Конструктор, вызываемый из роутера
    * 
    * @param mixed $type_section - секция URL, в которой содержится информация о размере и типе масштабирования изображения
    * @param mixed $picid_section - секция URL, в которой содержится id изображения
    * @return CImg_Handler_Interface
    */        
    function __construct()
    {
        if (!isset($this->srcFolder) || !isset($this->dstFolder)) {
            throw new \RS\Img\Exception(t("У класса %0 не заданы свойства srcFolder или dstFolder", array(get_class($this))));
        }
        $this->url = \RS\Http\Request::commonInstance();
    }
    
    function presetAct($act)
    {}
    
    function parseParameters()
    {
        if (preg_match('/^(.+?)_(\d+)x(\d+)$/', $this->url->get('type', TYPE_STRING), $match)) {
            $this->scale = $match[1];
            $this->width = $match[2];
            $this->height = $match[3];
        } else {
            throw new \RS\Img\Exception(t('Неверный URL картинки'));
        }
        
        if (preg_match('/^(.+?)_(.+?)$/', $this->url->get('picid', TYPE_STRING), $match)) {
            $ext = '.'.$this->url->get('ext', TYPE_STRING);
            $this->pic_id = $match[1].$ext;
            $this->hash = $match[2];
        } else {
            throw new \RS\Img\Exception(t('Неверный URL или неверная подпись ссылки'));
        }        
    }
    
    function exec()
    {
        $this->parseParameters();
        try {
            $img = new \RS\Img\Core(\Setup::$ROOT, \Setup::$FOLDER.$this->srcFolder, \Setup::$FOLDER.$this->dstFolder);
            $img->toOutput($this->pic_id, $this->width, $this->height, $this->scale, $this->hash);
        } catch (\RS\Img\Exception $e) {
            throw new \RS\Controller\ExceptionPageNotFound($e->getMessage(), get_class($this));
        }
        
        return true;        
    }
}


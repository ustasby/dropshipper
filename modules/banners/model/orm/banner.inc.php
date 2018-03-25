<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Banners\Model\Orm;
use \RS\Orm\Type;

class Banner extends \RS\Orm\OrmObject
{
    protected static
        $table = 'banner';
    
    public static
        $src_folder = '/storage/banners/original',
        $dst_folder = '/storage/banners/resized';    
    
    function _init()
    {        
        parent::_init()->append(array(
                'site_id' => new Type\CurrentSite(),
                'title' => new Type\Varchar(array(
                    'description' => t('Название баннера')
                )),
                'file' => new Type\File(array(
                    'description' => t('Баннер'),
                    'storage' => array(\Setup::$ROOT, \Setup::$FOLDER . static::$src_folder),
                    'template' => '%banners%/form/banner/file.tpl'
                )),
                'use_original_file' => new Type\Integer(array(
                    'description' => t('Использовать оригинал файла для вставки'),
                    'checkboxView' => array(1, 0)
                )),
                'link' => new Type\Varchar(array(
                    'description' => t('Ссылка')
                )),
                'targetblank' => new Type\Integer(array(
                    'description' => t('Открывать ссылку в новом окне'),
                    'checkboxView' => array(1, 0)
                )),
                'info' => new Type\Text(array(
                    'description' => t('Дополнительная информация')
                )),
                'public' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Публичный'),
                    'checkboxView' => array(1, 0)
                )),
                'xzone' => new Type\ArrayList(array(
                    'description' => t('Связанные зоны (удерживая CTRL можно выбирать несколько зон)'),
                    'list' => array(array('\Banners\Model\ZoneApi', 'staticAdminSelectList')),
                    'attr' => array(array(
                        'size' => 10,
                        'multiple' => 'multiple'
                    ))
                )),
                'weight' => new Type\Integer(array(
                    'description' => t('Вес от 1 до 100'),
                    'default' => 100,
                    'hint' => t('Чем больше вес, тем больше вероятность того, что баннер будет показан в случае конкуренции')
                )),
            t('Расписание'),
                'use_schedule' => new Type\Varchar(array(
                    'description' => t('Использовать показ по расписанию?'),
                    'checkboxview' => array(1, 0),
                    'default' => 0,
                    'checker' => array(array('\Banners\Model\Orm\Banner', 'staticUseScheduleCheck')),
                    'template' => '%banners%/form/banner/use_schedule.tpl'
                )),
                'date_start' => new Type\Datetime(array(
                    'description' => t('Дата начала показа'),
                    'visible' => false,
                )),
                'date_end' => new Type\Datetime(array(
                    'description' => t('Дата окончания показа'),
                    'visible' => false,
                ))
        ));
    }

    /**
     * Проверяем правильно ли установлено рассписание
     *
     * @param Banner $orm - сам объект баннера
     * @return boolean
     */
    public static function staticUseScheduleCheck(Banner $orm)
    {
        if (!$orm['use_schedule']) {
            return true;
        }
        return (!$orm['date_start'] || !$orm['date_end']) ? t('Укажите правильно даты начала и окончания показа по расписанию') : true;
    }

    /**
    * Возвращает отладочные действия, которые можно произвести с объектом
    * 
    * @return \RS\Debug\Action\AbstractAction[]
    */    
    function getDebugActions()
    {
        return array(
            new \RS\Debug\Action\Edit(\RS\Router\Manager::obj()->getAdminPattern('edit', array(':id' => '{id}'), 'banners-ctrl')),
            new \RS\Debug\Action\Delete(\RS\Router\Manager::obj()->getAdminPattern('del', array(':chk[]' => '{id}'), 'banners-ctrl'))
        );        
    }
    
    /**
    * Функция срабатывает после записи объекта
    * 
    * @param string $flag - флаг обозначающий какое действие выполняется. insert или update
    */
    function afterWrite($flag)
    {
        if ($this->isModified('xzone')) {
            \RS\Orm\Request::make()
                ->delete()
                ->from(new Xzone())
                ->where(array(
                    'banner_id' => $this['id']
                ))->exec();
            
            if ($this['xzone']) {
                foreach($this['xzone'] as $zone_id) {
                    if ($zone_id>0) {
                        $link = new Xzone();
                        $link['banner_id'] = $this['id'];
                        $link['zone_id'] = $zone_id;
                        $link->insert();
                    }
                }
            }
        }
    }
    
    /**
    * Возвращает клонированный объект баннера
    * @return Banner
    */
    function cloneSelf()
    {
        /**
        * @var \Banners\Model\Orm\Banner $clone
        */
        $clone = parent::cloneSelf();

        //Клонируем фото, если нужно
        if ($clone['file']){
           /**
           * @var \RS\Orm\Type\Image
           */
           $clone['file'] = $clone->__file->addFromUrl($clone->__file->getFullPath());
        }
        return $clone;
    }
    
    function fillZones()
    {
        $this['xzone'] = \RS\Orm\Request::make()
            ->from(new Xzone())
            ->where(array(
                'banner_id' => $this['id']
            ))->exec()->fetchSelected(null, 'zone_id');
    }
    
    function delete()
    {
        if ($result = parent::delete()) {
            \RS\Orm\Request::make()
                ->from(new Xzone())
                ->where(array(
                    'banner_id' => $this['id']
                ))->exec();
        }
        return $result;
    }
    
    /**
    * Возвращает путь к оригиналу файла 
    * 
    * @param bool $absolute Если true, то возвращает абсолютный путь, иначе возвращает относительный
    * @return string
    */
    function getOriginalUrl($absolute = false)
    {
        /**
        * @var \RS\Orm\Type\File
        */
        $link = $this['__file']->getLink();
        return $absolute ? \RS\Site\Manager::getSite()->getAbsoluteUrl($link) : $link;
    }
    
    /**
    * Возвращает путь к изображению с заданными размерами
    * 
    * @param integer $width - ширина изображения
    * @param integer $height - высота изображения
    * @param string $scale - тип масштабирования (xy|cxy|axy)
    * @param bool $absolute - если задано true, то будет возвращен абсолютный путь
    * @return string
    */
    function getImageUrl($width, $height, $scale = 'xy', $absolute = false)
    {
        //Пользуемся общей системой отображения картинок этой CMS.
        $img = new \RS\Img\Core(\Setup::$ROOT, \Setup::$FOLDER.static::$src_folder, \Setup::$FOLDER.static::$dst_folder);
        return $img->getImageUrl($this['__file']->getRealPath(), $width, $height, $scale, $absolute);
    }
    
    /**
    * Возвращает true, если файл баннера является файлом форматов jpg, gif, png
    * 
    * @return bool
    */
    function isImageFile()
    {
        $filename = $this['__file']->getRealPath();
        list($name, $ext) = \RS\File\Tools::parseFileName($filename, true);
        return in_array(strtolower($ext), array('jpg', 'gif', 'png'));
    }
    
    /**
    * Возвращает ссылку на сформированное изображение или на оригинал файла, 
    * в зависимости от опции баннера
    * 
    * @return string
    */
    function getBannerUrl($width = null, $height = null, $scale = 'xy', $absolute = false)
    {
        if ($this['use_original_file'] || !$width || !$height) {
            return $this->getOriginalUrl($absolute);
        } else {
            return $this->getImageUrl($width, $height, $scale, $absolute);
        }
    }

    /**
     * Возвращает одну строку из описания к баннеру
     *
     * @param integer $n - номер строки
     * @return string
     */
    function getInfoLine($n)
    {
        $lines = explode("\n", $this['info']);
        return isset($lines[$n]) ? trim($lines[$n]) : '';
    }
}

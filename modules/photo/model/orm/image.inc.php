<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Photo\Model\Orm;
use Photo\Model\PhotoApi;
use \RS\Orm\Type;

/**
* Фотография объекта
* @ingroup Photo
*/
class Image extends \RS\Orm\OrmObject
{
    protected static
        $table = 'images';
    
    protected
        $img_core,
        $srcFolder = '/storage/photo/original',
        $dstFolder = '/storage/photo/resized';
    
    function __construct($id = null, $cache = true)
    {
        $this->srcFolder = \Setup::$FOLDER.$this->srcFolder;
        $this->dstFolder = \Setup::$FOLDER.$this->dstFolder;        
        
        parent::__construct($id, $cache);
        $this->img_core = new \RS\Img\Core(\Setup::$ROOT, $this->srcFolder, $this->dstFolder);
    }
        
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'servername' => new Type\Varchar(array(
                'description' => t('Имя файла на сервере'),
                'maxLength' => 25,
                'index' => true
            )),
            'filename' => new Type\Varchar(array(
                'description' => t('Оригинальное имя файла'),
                'maxLength' => 255
            )),
            'view_count' => new Type\Integer(array(
                'description' => t('Количество просмотров')
            )),
            'size' => new Type\Integer(array(
                'description' => t('Размер файла')
            )),
            'mime' => new Type\Varchar(array(
                'description' => t('Mime тип изображения'),
                'maxLength' => 20
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Порядковый номер'),
                'allowempty' => false
            )),
            'title' => new Type\Text(array(
                'description' => t('Подпись изображения')
            )),
            'type'  => new Type\Varchar(array(
                'description' => t('Название объекта, которому принадлежат изображения'),
                'maxLength' => 20
            )),
            'linkid' => new Type\Integer(array(
                'description' => t('Идентификатор объекта, которому принадлежит изображение')
            )),
            'extra' => new Type\Varchar(array(
                'description' => t('Дополнительный символьный идентификатор изображения'),
                'maxLength' => 255
            ))
        ));
        
        $this
            ->addIndex(array('servername', 'type', 'linkid'), self::INDEX_UNIQUE)
            ->addIndex(array('linkid', 'type'))
            ->addIndex(array('linkid', 'sortn'));
    }
    
    function getFolders()
    {
    	return array(
    		'srcFolder' => $this->srcFolder,
    		'dstFolder' => $this->dstFolder
    	);
	}
    
    /**
    * При создании записи sortn - ставим максимальный, т.е. добавляем фото в конец.
    */
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG)
        {
            $this['sortn'] = \RS\Orm\Request::make()
                ->select('MAX(sortn)+1 as next_sort')
                ->from($this)
                ->where(array(
                    'linkid' => $this['linkid'],
                    'type' => $this['type']
                ))
                ->exec()->getOneField('next_sort', 0);
        }
        return true;
    }
    
    /**
    * Возвращает относительный URL к картинке
    * 
    * @param int $width - ширина
    * @param int $height - высота
    * @param string (xy|cxy|axy) $img_type - тип картинки
    * @param bool $absolute - если True, то будет возвращен абсолютный путь, иначе относительный
    * @return string URL
    */
    function getUrl($width, $height, $img_type = 'xy', $absolute = false)
    {
        //Пользуемся общей системой отображения картинок этой CMS.
        return $this->img_core->getImageUrl($this['servername'], $width, $height, $img_type, $absolute);
    }
    
    /**
    * Возвращает объект системы картинок для этой CMS
    * @return \RS\Img\Core
    */
    function getImageCore()
    {
        return $this->img_core;
    }
    
    /**
    * Возвращает URL файла оригинала
    * 
    * @param boolean $absolute - флаг отвечает за, то какую ссылку отображать абсолютную или относительную
    * 
    * @return string
    */
    function getOriginalUrl($absolute = false)
    {
        $url = $this->srcFolder.'/'.$this['servername'];
        return $absolute ?  \RS\Site\Manager::getSite()->getAbsoluteUrl($url) : $url;
    }
    
    function delete()
    {
        $remain = \RS\Orm\Request::make()->from($this)->where(array('servername' => $this['servername']))->count();
        if ($result = parent::delete()) {
            if ($remain<2) {
                $img = new \RS\Img\Core(\Setup::$ROOT, $this->srcFolder, $this->dstFolder);
                $img->removeFile($this['servername']);
            }
            
            // Исправление порядковых номеров сортировки сестринских изображений
            $photo_api = new PhotoApi;
            $photo_api->fixSortNumbers($this['linkid'], $this['type']);
        }
        return $result;
    }
    
    /**
    * Перемещает элемент на новую позицию. 0 - первый элемент
    * 
    * @param mixed $new_position
    */
    public function moveToPosition($new_position)
    {
        if ($this->noWriteRights()) return false;
        
        $q = \RS\Orm\Request::make()
            ->update($this)
            ->where(array(
                'linkid' => $this['linkid'],
                'type' => $this['type']
            ));
        
        //Определяем направлене перемещения 
        if ($this['sortn'] < $new_position) {
            //Вниз
            $q->set('sortn = sortn - 1')
            ->where("sortn > '#cur_pos' AND sortn <= '#new_pos'", array('cur_pos' => $this['sortn'], 'new_pos' => $new_position));
        } else { 
            //Вверх
            $q->set('sortn = sortn + 1')
                ->where("sortn >= '#new_pos' AND sortn < '#cur_pos'", array('cur_pos' => $this['sortn'], 'new_pos' => $new_position));
        }
        $q->exec();
        
        \RS\Orm\Request::make()
            ->update($this)
            ->set(array('sortn' => $new_position))
            ->where(array(
                'id' => $this['id']
            ))
            ->exec();
        return true;
    }
    
    /**
    * Переворачивает изображение на 90 градусов.
    * 
    * @param string cw | ccw $direction направление переворота. cw - по часовой стралке, ccw - против часовой стрелки
    */
    function rotate($angle)
    {
        $img = new \RS\Img\Core(\Setup::$PATH, $this->srcFolder, $this->dstFolder);
        $img->rotate($this['servername'], $angle);
    }
    
}


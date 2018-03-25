<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Photo\Model;

class PhotoApi extends \RS\Module\AbstractModel\EntityList
{
    const
        MOVE_TYPE_UPLOAD = 'upload', //move_uploaded_file
        MOVE_TYPE_RENAME = 'rename',
        MOVE_TYPE_COPY = 'copy',
        IMGUNIQ_TOTAL_LENGTH = 15; //общая длина имени файла изображения
        
    protected
        /**
        * @var \Photo\Model\Orm\Image
        */
        $obj_instance,
        $enable_upload_resize = true,
        $uploadError = array(),
        $allow_mime = array('image/pjpeg', 'image/jpeg', 'image/png', 'image/gif'),
        $srcFolder = '/storage/photo/original',
        $dstFolder = '/storage/photo/resized';
        
    function __construct()
    {
        $order = 'sortn';
        $config = \RS\Config\Loader::byModule($this);
        //Если включена настройка показ фотографий в обратом порядке
        if ($config['product_sort_photo_desc']){
            $order = 'sortn desc';//Сортировка в обратном порядке
        }
    	parent::__construct(new \Photo\Model\Orm\Image, array(
            'defaultOrder' => $order
        ));
        
        $this->srcFolder = \Setup::$PATH. $this->srcFolder;
        $this->dstFolder = \Setup::$PATH. $this->dstFolder;
    }
    
    /**
    * Возвращает имя каталога с оригиналами загруженных фотографий
    * @return string
    */
    function getSourceFolder()
    {
        return $this->srcFolder;
    }
    
    /**
    * Получает фотографии связанные с объектом
    * 
    * @param integer $linkid - id прилинкованного объекта
    * @param string $type - тип ссылки
    * @return \Photo\Model\Orm\Image[]
    */
    function getLinkedImages($linkid, $type)
    {
        $this->setFilter('linkid', $linkid);
        $this->setFilter('type', $type);
        //$this->setOrder($this->default_order);
        return $this->queryObj()->objects($this->obj_instance, 'id');
    }


    /**
     * Исправляет порядоковые номера сортировки.
     * Актуально, например, при удалении картинок.
     *
     * @param $linkid
     * @param $type
     */
    function fixSortNumbers($linkid, $type)
    {
        $linkid = (int)$linkid;
        \RS\Db\Adapter::SQLExec("SET @rownumber=0");
        \RS\Db\Adapter::SQLExec("
            UPDATE {$this->obj_instance->_getTable()} set `sortn` = (@rownumber := @rownumber + 1) - 1
            WHERE `linkid` = '#linkid' AND `type` = '#type'
            order by `sortn` asc
        ", array(
            'linkid' => $linkid,
            'type' => $type
        ));
    }

    
    /**
    * Генерирует уникальное имя картинки, в папке. Например: "a/h32k45h6hn"
    */
    public static function generateImgName()
    {
        $folder_symbols = 'abcdefghi';
        $folder = $folder_symbols[ mt_rand(0, strlen($folder_symbols)-1) ];
        
        $symbols = '01234abcdefghi567jklmnopqrstuvwxyz890';
        $uniq = '';
        for ($i=0; $i<self::IMGUNIQ_TOTAL_LENGTH; $i++) {
            $uniq .= $symbols[ mt_rand(0, strlen($symbols)-1) ];
        }
        return $folder.'/'.$uniq;
    }    
    
    /**
    * Возвращает расширение по типу файла
    * 
    * @param integer $type тип изображения. см константы IMAGETYPE_...
    * @return string
    */
    public static function getExtensionByType($type)
    {
        switch($type) {
            case IMAGETYPE_GIF: $ext = 'gif'; break;
            case IMAGETYPE_PNG: $ext = 'png'; break;
            default: $ext = 'jpg';
        }
        return $ext;
    }
    
    
    /**
    * Загрузка файла из URL
    * 
    * @param string $url - Путь к файлу изображения
    * @param mixed $type - Категория изображений
    * @param array | int $linkid - ID объекта, которому принадлежит изображение
    * @param bool $copy - Если true, то файл будет скопирован из расположения, иначе перенесен
    * @param string $extra - Произвольный идентификатор
    * @param bool $enable_resize - если true, то то изображение будет пережиматься до размеров, 
    *                              указанных в настройках модуля "Блок фотографий", иначе исходное
    *                              изображение будет импортировано.
    * @return bool|\Photo\Model\Orm\Image
    */
    function addFromUrl($url, $type, $linkid, $copy = false, $extra = null, $enable_resize = true)
    {
        $file = array(
            'name' => basename($url)
        );
        
        if (!($image_info = @getimagesize($url))) {
            $this->uploadError[] = "{$file['name']}: ".t('Неверный формат изображения');
            return false;
        }
        if (!isset($image_info[2]) || !in_array($image_info[2], array(1,2,3)))
        {
            $this->uploadError[] = "{$file['name']}: ".t('Загружен недопустимый тип изображения. (допускается JPG, PNG, GIF)');
            return false;
        }
        
        $servername = self::generateImgName().'.'.self::getExtensionByType($image_info[2]);
        
        $result = $this->moveUploadedFile($url, $this->srcFolder.'/'.$servername, $copy ? self::MOVE_TYPE_COPY : self::MOVE_TYPE_RENAME, $enable_resize);
        
        if (  $result ) {
            foreach((array)$linkid as $one_linkid) {
                /**
                 * @var \Photo\Model\Orm\Image $photo
                 */
                $photo = $this->getNewElement();
                $photo['filename']   = $file['name'];
                $photo['servername'] = $servername;
                $photo['size']       = (int)filesize($this->srcFolder.'/'.$servername);
                $photo['mime']       = $image_info['mime'];
                $photo['type']       = $type;
                $photo['linkid']     = $one_linkid;
                if ($extra !== null) {
                    $photo['extra'] = $extra;
                }    
                $photo->insert();
            }
            return $photo;
        } else {
            $this->uploadError[] = "{$file['name']}: ".t('Ошибка перемещения файла из временной папки');
            return false;
        }
    }


    /**
     * Загрузка фотографий из POST
     *
     * @param array $file - массив из $_FILES['имя Inputа']
     * @param null $type - Название объекта, которому принадлежат изображения (Например catalog)
     * @param null $linkid - id связанное с объектом
     * @param null $title - Название фото
     * @param bool $enable_resize - уменьшать большие фото до размера указанного конфиге
     *
     * @return bool|\Photo\Model\Orm\Image
     */
    function uploadImage(array $file, $type = null, $linkid = null, $title = null, $enable_resize = true)
    {
        if ($acl_err = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            $this->uploadError[] = $acl_err;
            return false;
        }

        if ($file['error'] == UPLOAD_ERR_NO_FILE) return false;
        if ($file['error'] != UPLOAD_ERR_OK)
        {
            $this->uploadError[] = "{$file['name']}: ".$this->checkUploadError($file['error']);
        } else 
        {
            $image_info = getimagesize($file['tmp_name']);
            if (!in_array($file['type'], $this->allow_mime) || !isset($image_info[2]) || !in_array($image_info[2], array(1,2,3)))
            {
                $this->uploadError[] = t("Загружен недопустимый тип изображения.(допускается JPG, PNG, GIF)");
                return false;
            }
            
            $servername = self::generateImgName().'.'.self::getExtensionByType($image_info[2]);
            
            if ($this->moveUploadedFile($file['tmp_name'], $this->srcFolder.'/'.$servername, self::MOVE_TYPE_UPLOAD, $enable_resize))
            {
                /**
                 * @var \Photo\Model\Orm\Image $photo
                 */
                $photo = $this->getNewElement();
                $photo['filename']   = $file['name'];
                $photo['servername'] = $servername;
                $photo['size']       = (int)$file['size'];
                $photo['mime']       = $image_info['mime'];
                $photo['type']       = $type;
                $photo['linkid']     = $linkid;
                $photo['title']      = $title;
                $photo->insert();
            } else {
                $this->uploadError[] = t("Ошибка перемещения файла из временной папки");
                return false;
            }
            return $photo;
        }
        return false;
    }
    
    /**
    * Сохраняет фотографию в хранилище оригиналов с учетом настроек модуля
    * 
    * @param string $source - путь к исходному файлу изображения
    * @param string $destination - путь к файлу изображения для сохранения
    * @param string $move_type - тип перемещения загрженного файла
    * @param bool $enable_resize - уменьшать большие фото до размера указанного конфиге
    * @return mixed
    */
    function moveUploadedFile($source, $destination, $move_type = self::MOVE_TYPE_UPLOAD, $enable_resize = true )
    {
        $config = \RS\Config\Loader::byModule($this);
        \RS\File\Tools::makePath($destination, true);
        
        if ($config['original_photos_resize'] && $enable_resize) {
            $source_img = new \RS\Img\File($source);
            $resizer = new \RS\Img\Type\Xy();
            $result = $resizer->resizeImage($source_img, $destination, $config['original_photos_width'], $config['original_photos_height'], 100);
            if ($move_type == self::MOVE_TYPE_RENAME) {
                unlink($source);
            }
        } else {
            switch ($move_type) {
                case self::MOVE_TYPE_UPLOAD:
                    $result = move_uploaded_file($source, $destination);
                    break;
                case self::MOVE_TYPE_RENAME:
                    $result = rename($source, $destination);
                    break;
                case self::MOVE_TYPE_COPY:
                    $result = copy($source, $destination);
                    break;
            }
        }
        \RS\Db\Adapter::disconnect(); // Защита от убегания MySQL
        \RS\Db\Adapter::connect();
        return $result;
    }

    /**
     * Проверяет ответ сервера на загрузку файла на известные ошибки. Если ошибок нет, то false, иначе возвращается пояснение к ошибке
     *
     * @param integer $err_status - идентификатор статуса ошибки
     * @return bool|string
     */
    protected function checkUploadError($err_status)
    {
        switch($err_status) 
        {
            case UPLOAD_ERR_INI_SIZE: $res = t('Загрузка файлов такого размера не поддерживается сервером'); break;
            case UPLOAD_ERR_FORM_SIZE: $res = t('Загружен слишком большой файл'); break;
            case UPLOAD_ERR_PARTIAL: $res = t('Файл загружен частично'); break;
            case UPLOAD_ERR_NO_FILE: $res = t('Не выбран файл для загрузки'); break;
            case UPLOAD_ERR_NO_TMP_DIR: $res = t('На сервере не обнаружена временная папка'); break;
            case UPLOAD_ERR_CANT_WRITE: $res = t('Не возможно записать файл на сервер'); break;
            default: $res = false;
        }
         return $res;
    }

    /**
     * Очищает ошибки после загрузки файлов
     */
    function cleanUploadError()
    {
        $this->uploadError = array();
    }

    /**
     * Возвращает массив ошибок при загрузке
     *
     * @return array
     */
    function getUploadError()
    {
        
        return $this->uploadError;
    }
    
    function getPrevNextPhoto(Core_List $photo_list, $cur_photo)
    {
        $ret = array('prev' => null, 'next' => null);
        for($i=0; $i< $photo_list->count(); $i++)
        {
            if ($photo_list[$i]['id'] == $cur_photo)
            {
                if ($i>0) $ret['prev'] = $photo_list[$i-1];
                if ($i<($photo_list->count()-1)) $ret['next'] = $photo_list[$i+1];
            }
        }
        return $ret;
    }
    
    /**
    * Возвращает объект заглушки
    */
    function stub()
    {
        return new Stub();
    }
    
    /**
    * Массово удаляет связанные картинки всех размеров и ссылки на них.
    */
    function multiDelete($ids)
    {
        if (!empty($ids))
        {
            //Загружаем сопоставление id => Серверное имя изображения
            $server_names = \RS\Orm\Request::make()
                ->select('id, servername')
                ->from($this->obj_instance)
                ->whereIn("id", $ids)
                ->exec()->fetchSelected('id', 'servername');
                
            \RS\Orm\Request::make()->delete()->from($this->obj_instance)->whereIn('id', $ids)->exec();
            
            //Составляем список файлов, на которые остались ссылки в базе
            $except = \RS\Orm\Request::make()
                ->select('id,servername')
                ->from($this->obj_instance)
                ->whereIn("servername", $server_names)
                ->exec()->fetchSelected('servername', 'id');
            
            //Удаляем файлы, на которые больше нет ссылок в базе
            $img_core = $this->obj_instance->getImageCore();
                        
            $folders = $this->obj_instance->getFolders();
            foreach($server_names as $id=>$server_name) {
                if (!isset($except[$server_name])) {
                    $img_core->removeFile($server_name);
                }
            }
        }
    }
    
    /**
    * Удаляет фото, на которые нет ссылок в базе
    * 
    * @return integer возвращает количество удаленных оригиналов изображений
    */
    function deleteUnlinkedPhotos()
    {
        $exists = \RS\Orm\Request::make()
            ->select('id, servername')
            ->from(new $this->obj_instance)
            ->exec()->fetchSelected('servername', 'id');
        
        $folders = $this->obj_instance->getFolders();
        return $this->delRecursive( \Setup::$PATH.'/'.$folders['srcFolder'], '', $exists);
    }
    
    /**
    * Удаляет автоматически сгенерированные миниатюры фото
    * 
    * @return bool
    */
    function deletePreviewPhotos()
    {
        $result = \RS\File\Tools::deleteFolder(\Setup::$PATH.'/storage/photo/stub/resized');
        $result = \RS\File\Tools::deleteFolder($this->dstFolder, true) && $result;
        return $result;
    }    
    
    /**
    * Рекурсивно удаляет изображения
    * 
    * @param string $dir_base - корневая папка с оригиналами фото
    * @param string $file_base - папка относительно корня фото
    * @param array $exists - массив с именами файлов в ключе, которые не нужно удалять
    * @return integer возвращает количество удаленных оригинальных изображений
    */
    protected function delRecursive($dir_base, $file_base, $exists)
    {
        $count = 0;
        $dir = $dir_base.'/'.$file_base;
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file == '.' || $file == '..') continue;
                    if (is_dir($dir.$file)) {
                        $count += $this->delRecursive($dir_base, $file_base.$file.'/', $exists);
                    } else {
                        if (!isset($exists[$file_base.$file])) {
                            $count++;
                            $this->obj_instance->getImageCore()->removeFile($file_base.$file);
                        }
                    }
                }
                closedir($dh);
            }
        }
        return $count;
    }
    
    
}


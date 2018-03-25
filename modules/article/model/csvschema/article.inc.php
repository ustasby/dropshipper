<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Article\Model\CsvSchema;
use \RS\Csv\Preset,
    \Article\Model\Orm;

/**
* Схема экспорта/импорта характеристик в CSV
*/
class Article extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new Orm\Article(),
            'temporaryId' => true,
            'excludeFields' => array('id', 'site_id', 'parent', 'image'),
            'multisite' => true,
            'searchFields' => array('title', 'parent'),
            'selectRequest' => \RS\Orm\Request::make()
                ->select('A.*, C.title as dir_title, C.alias as dir_alias')
                ->from(new Orm\Article(), 'A')
                ->leftjoin(new Orm\Category(), 'C.id = A.parent', 'C')
                ->where(array(
                    'A.site_id' => \RS\Site\Manager::getSiteId()
                ))
                ->orderby('A.parent')
        )), 
        array(
            new Preset\SinglePhoto(array(
                'linkPresetId' => 0,
                'linkForeignField' => 'image',
                'title' => t('Изображение')
            )),
            new Preset\TreeParent(array(
                'ormObject' => new Orm\Category(),
                'fieldsMap' => array(
                    'dir_alias' => 'alias',
                    'dir_title' => 'title'
                ),
                'titles' => array(
                    'dir_title' => t('Категория'),
                    'dir_alias' => t('Псевдоним категории')
                ),
                'idField' => 'id',
                'fields' => array('dir_alias'),
                'treeField' => 'dir_title',
                'parentField' => 'parent',
                'rootValue' => 0,
                'multisite' => true,
                'linkForeignField' => 'parent',
                'linkPresetId' => 0
            )),
            new Preset\PhotoBlock(array(
                'typeItem' => \Article\Model\Orm\Article::IMAGES_TYPE,
                'linkPresetId' => 0,
                'linkIdField' => 'id'
            )), 
            new Preset\Tags(array(
                'item'         => 'article',
                'linkPresetId' => 0,
                'linkIdField'  => 'id'
            )),
        ), 
        array(
            'beforeLineImport' => array(__CLASS__, 'beforeLineImport')
        ));
    }
    
    public static function beforeLineImport($_this)
    {
        //Устанавливаем временный id
        $time = -time();
        $_this->getPreset(0)->row['id'] = $time;
        $_this->getPreset(0)->row['_tmpid'] = $time;
    }
    
}
?>
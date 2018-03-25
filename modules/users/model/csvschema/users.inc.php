<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Model\CsvSchema;
use \RS\Csv\Preset,
    \Users\Model\CsvPreset as UsersPreset,
    \Users\Model\Orm;

/**
* Схема импорта/экспорта в CSV файл пользователей
*/
class Users extends \RS\Csv\AbstractSchema
{
    function __construct()
    {        
        $config = \RS\Config\Loader::byModule($this);
        
        parent::__construct(
            new Preset\Base(array(
                'ormObject'     => new Orm\User(),
                'excludeFields' => array(
                    'id', 'pass', 'hash', 'balance', 'balance_sign', 'data', 'changepass', '_serialized', 'captcha', 'user_cost', 'cost_id', 'no_send_notice'
                ),
                'extraFields' => array( //Поля невидимые или runtime становятся видимыми, если заданы в данном массиве
                    'openpass','pass'
                ),
                'savedRequest' => \Users\Model\Api::getSavedRequest('Users\Controller\Admin\Ctrl_list'), //Объект запроса из сессии с параметрами текущего просмотра списка
                'searchFields' => $config['csv_id_fields'],
                'beforeRowImport' => function($_this) {
                    $_this->row['no_send_notice'] = true;
                }
            )),
            array(
               new UsersPreset\Groups(array(
                    'title'        => t('Группы пользователя'),
                    'linkPresetId' => 0,
                    'delimeter'    => ',',
                    'arrayField'    => 'groups',
                    'linkIdField'  => 'user',
               )),
               new UsersPreset\Cost(array(
                    'title'        => t('Тип цены'),
                    'linkPresetId' => 0,
                    'arrayField'   => 'user_cost',
                    'linkIdField'  => 'id',
               )),
               new Preset\SerializedArray(array(   
                    'linkPresetId' => 0,
                    'linkForeignField' => '_serialized',
                    'title' => t('Дополнительные поля')
               )),
            )
        );
    }

    /**
    * Возвращает возможные колонки для идентификации пользователя
    * 
    * @return array
    */
    public static function getPossibleIdFields()
    {
        $product = new \Users\Model\Orm\User();
        $fields = array_flip(array('name', 'surname', 'midname', 'e_mail', 'login', 'phone'));
        foreach($fields as $k => $v) {
            $fields[$k] = $product['__'.$k]->getTitle();
        }
        return $fields;
    }    
}


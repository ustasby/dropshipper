<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Config;
use \RS\Orm\Type;

class File extends \RS\Orm\ConfigObject
{
    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                'generate_password_length' => new Type\Integer(array(
                    'description' => t('Длина пароля для генерации')
                )),
                'generate_password_symbols' => new Type\Varchar(array(
                    'description' => t('Символы для генерации паролей')
                )),
            t('Дополнительные поля'),
                '__userfields__' => new Type\UserTemplate('%users%/form/config/userfield.tpl'),
                'userfields' => new Type\ArrayList(array(
                    'description' => t('Дополнительные поля'),
                    'runtime' => false,
                    'visible' => false
                )),
            t('Обмен данными в CSV'),
                'csv_id_fields' => new Type\ArrayList(array(
                    'description' => t('Поля для идентификации пользователя при импорте (удерживая CTRL можно выбрать несколько полей)'),
                    'hint' => t('Во время импорта данных из CSV файла, система сперва будет обновлять пользователей, у которых будет совпадение значений по указанным здесь колонкам. В противном случае будет создаваться новый пользователь'),
                    'list' => array(array('\Users\Model\CsvSchema\Users','getPossibleIdFields')),
                    'size' => 7,
                    'attr' => array(array('multiple' => true))
                )),
            t('Логирование'),
                'clear_for_last_time' => new Type\Integer(array(
                    'description' => t('За сколько последних часов очищать логи пользователей?'),
                    'default' => 2160,
                    'size' => 7
                )),
                'clear_random' => new Type\Integer(array(
                    'description' => t('Вероятность очищения лога пользователей в (1-100%)'),
                    'default' => 5,
                    'size' => 5
                )),
        ));
    }      
    
    /**
    * Возвращает объект, отвечающий за работу с пользовательскими полями.
    * 
    * @return \RS\Config\UserFieldsManager
    */
    function getUserFieldsManager()
    {
        return new \RS\Config\UserFieldsManager($this['userfields'], null, 'userfields');
    }
}

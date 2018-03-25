<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Model;
use \RS\Orm\Type,
    \RS\Helper\CustomView;

/**
* Класс содержит API функции дополтельные для работы в системе в рамках задач по модулю пользователя
*/
class ApiUtils
{
    /**
    * Возвращает секцию с дополнительными полями пользователя из конфига для внешнего API
    * 
    */
    public static function getAdditionalUserFieldsSection()
    {
        //Добавим доп поля для пользователя для регистрации
        $reg_fields_manager = \RS\Config\Loader::byModule('users')->getUserFieldsManager();
        $reg_fields_manager->setErrorPrefix('regfield_');
        $reg_fields_manager->setArrayWrapper('regfields');
        
        //Пройдёмся по полям
        $fields = array();
        foreach ($reg_fields_manager->getStructure() as $field){
            if ($field['type'] == 'bool'){  //Если тип галочка
                $field['val'] = $field['val'] ? true : false;    
            }
            $fields[] = $field;
        }
        
        return $fields;
    }

    /**
     * Возвращает дополнительные параметры отображения для пользователя
     * Необходимо возвращать массив
     * [
     *    [
     *      'title' => 'Баланс',
     *      'value' => '320 p.'
     *    ]
     * ]
     *
     */
    public static function getAdditionalUserInfoFieldsSection()
    {
        $user_info = array();

        //Добавим сведения по лицевому счету
        if (\RS\Module\Manager::staticModuleExists('shop') && \RS\Application\Auth::isAuthorize()){
            $config = \RS\Config\Loader::byModule('shop');

            $user = \RS\Application\Auth::getCurrentUser();
            if ($config['use_personal_account']){
                $user_info[] = array(
                    'title' => t('Баланс'),
                    'value' => $user->getBalance(true, true)
                );
            }
        }

        return $user_info;
    }
}
<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Model\ExternalApi\User;

/**
* Возвращает поля добавленные к пользователю в мадуле магазин
*/
class AdditionalFields extends \ExternalApi\Model\AbstractMethods\AbstractMethod
{
    const
        RIGHT_LOAD = 1;
    
    /**
    * Возвращает комментарии к кодам прав доступа
    * 
    * @return [
    *     КОД => КОММЕНТАРИЙ,
    *     КОД => КОММЕНТАРИЙ,
    *     ...
    * ]
    */
    public function getRightTitles()
    {
        return array(
            self::RIGHT_LOAD => t('Получение данных по полям')
        );
    }
    


    /**
    * Возвращает поля добавленные к пользователю
    * 
    * @example GET /api/methods/user.additionalfields
    * 
    * Ответ:
    * <pre>
    * {
    *        "response": {
    *           "fields": [
    *               {
    *                   "alias": "pole",
    *                   "maxlength": "",
    *                   "necessary": "1",
    *                   "title": "Поле",
    *                   "type": "string",
    *                   "values": "",
    *                   "val": "",
    *                   "current_val": ""
    *               },
    *               {
    *                    "alias": "text",
    *                    "maxlength": "",
    *                    "necessary": "",
    *                    "title": "Текст",
    *                    "type": "text",
    *                    "values": "",
    *                    "val": "",
    *                    "current_val": ""
    *                },
    *                {
    *                    "alias": "spisok",
    *                    "maxlength": "",
    *                    "necessary": "",
    *                    "title": "Список",
    *                    "type": "list",
    *                    "values": "10 лет, 20 лет, 30 лет",
    *                    "val": "20 лет",
    *                    "current_val": "20 лет"
    *                },
    *                {
    *                    "alias": "yesno",
    *                    "maxlength": "",
    *                    "necessary": "",
    *                    "title": "Да нет",
    *                    "type": "bool",
    *                    "values": "",
    *                    "val": false,
    *                    "current_val": "Нет"
    *                }
    *           ]
    * }
    * </pre>
    * 
    * @return array Возращает, пустой массив ошибок, если всё успешно
    */
    protected function process()
    {
        $response['response']['fields'] = \Users\Model\ApiUtils::getAdditionalUserFieldsSection();
                  
        return $response;
    }
}
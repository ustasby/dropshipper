<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace PushSender\Model\Orm;
use \RS\Orm\Type;

/**
* Объект Push уведомления для отправки пользователям
*/
class PushTokenMessage extends \RS\Orm\FormObject
{
    const //Типы сообщений
        TYPE_SIMPLE   = "Simple",
        TYPE_PAGE     = "Page",
        TYPE_PRODUCT  = "Product",
        TYPE_CATEGORY = "Category";
    
    function __construct()
    {
        parent::__construct(new \RS\Orm\PropertyIterator(
            array(
                'title' => new Type\Varchar(array(
                    'description' => t('Заголовок сообщения'),
                )),      
                'body' => new Type\Varchar(array( 
                    'description' => t('Краткий текст'),
                )),
                '_send_type_' => new Type\Varchar(array(
                    'description' => t('Тип уведомления'),
                    'template' => '%pushsender%/form/pushtokenmessage/type.tpl',
                    'runtime' => true
                )),
                'send_type' => new Type\Varchar(array(
                    'description' => t('Тип уведомления'),
                    'listFromArray' => array(array(
                        self::TYPE_SIMPLE => t('Текcтовое сообщение'),
                        self::TYPE_PAGE => t('Страница с текстом'),
                        self::TYPE_PRODUCT => t('Переход на товар'),
                        self::TYPE_CATEGORY => t('Переход в директорию')
                    )),
                    'visible' => false
                )),
                'message' => new Type\Richtext(array(
                    'description' => t('Текст для отправки пользователям'),
                    'visible' => false
                )),
                'product_id' => new Type\Integer(array(
                    'description' => t('Товар для показа пользователю'),
                    'template' => '%pushsender%/form/pushtokenmessage/product_id.tpl',
                    'visible' => false
                )),
                'category_id' => new Type\Integer(array(
                    'description' => t('Категория для показа пользователю'),
                    'template' => '%pushsender%/form/pushtokenmessage/category_id.tpl',
                    'visible' => false
                ))
            )
        ));
    }
}                    

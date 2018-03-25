<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Model\ExportType\Yandex\OfferType;
use \RS\Orm\Type;

/**
* Структура данных, описывающая поле в экспортируемом XML документе
*/
class Field
{
    public $name;
    public $hint;
    public $title;
    public $type = TYPE_STRING;
    public $required = false;
    public $maxlen = 0;
    public $hidden = false;
    public $boolAsInt = false;
}
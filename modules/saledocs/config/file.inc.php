<?php
namespace SaleDocs\Config;
use \RS\Orm\Type;

class File extends \RS\Orm\ConfigObject
{
    function _init()
    {
        parent::_init()->append(array(
            'cache_pdf' => new Type\Integer(array(
                'description' => t('Кэшировать на диске сгенерированные PDF файлы'),
                'maxLength' => 1,
                'checkboxView' => array(1,0),
                'hint' => t('Значительно ускоряет повторное открытие документа')
            )),
            'contract_tpl' => new Type\Template(array(
                'onlyThemes' => false,
                'description' => t('Шаблон договора')
            )),
            'act_tpl' => new Type\Template(array(
                'onlyThemes' => false,
                'description' => t('Шаблон акта выполненных работ')
            )),
            'bill_tpl' => new Type\Template(array(
                'onlyThemes' => false,
                'description' => t('Шаблон счета')
            ))
        ));
    }
}

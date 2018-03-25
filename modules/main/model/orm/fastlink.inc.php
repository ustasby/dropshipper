<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Model\Orm;

use RS\Orm\OrmObject;
use RS\Orm\Type;

/**
 * ORM объект одной быстрой ссылки
 */
class FastLink extends OrmObject
{
    const
        TARGET_WINDOW = 'window',
        TARGET_BLANK = 'blank';

    protected static
        $table = 'fast_link';

    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'title' => new Type\Varchar(array(
                'description' => t('Название ссылки')
            )),
            'link' => new Type\Varchar(array(
                'description' => t('Ссылка'),
                'hint' => t('Можно использовать абсолютную или относительную ссылку. Например: https://example.com/addons/ или /addons/')
            )),
            'target' => new Type\Enum(array(self::TARGET_WINDOW, self::TARGET_BLANK), array(
                'description' => t('Открывать'),
                'listFromArray' => array(array(
                    self::TARGET_WINDOW => t('В текущем окне'),
                    self::TARGET_BLANK => t('В новом окне')
                ))
            )),
            'icon' => new Type\Varchar(array(
                'description' => t('Иконка'),
                'list' => array(array('\Main\Model\IconReference', 'getSelectList')),
                'changeSizeForList' => false,
                'attr' => array(
                    array('class' => 'zmdi', 'size' => 10)
                ),
                'default' => 'zmdi-open-in-new'
            )),
            'bgcolor' => new Type\Color(array(
                'description' => t('Цвет фона иконки'),
                'default' => '#eeeeee'
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Порядок'),
                'visible' => false
            ))
        ));
    }

    /**
     * Выполняется перед сохранением объекта
     * @param string $flag
     * @return void
     */
    public function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $this['sortn'] = \RS\Orm\Request::make()
                    ->select('MAX(sortn) as max')
                    ->from($this)
                    ->exec()->getOneField('max', 0) + 1;
        }
    }
}
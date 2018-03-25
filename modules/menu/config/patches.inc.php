<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Menu\Config;
use \RS\Orm\Type;

/**
* Патчи к модулю
*/
class Patches extends \RS\Module\AbstractPatches
{
    /**
    * Возвращает массив имен патчей.
    * 
    * @return array
    */
    function init()
    {
        return array(
            '20038',
            '20035',
            '20023'
        );
    }
    
    /**
    * Сбрасываем предыдущее закэшированное состояние объекта меню
    */
    function beforeUpdate20038()
    {
        $menu = new \Menu\Model\Orm\Menu();
        $menu->getPropertyIterator()->append(array(
             'content' => new Type\Richtext(array(
                    'description' => t('Статья'),
                )),
        ));
        $menu->dbUpdate();
    }

    /**
     * Переносим привязаные статьи в объекты Меню
     *
     * @throws \RS\Db\Exception
     */
    function afterUpdate20038()
    {
        //Загружаем статьи
        $linked_articles = \RS\Orm\Request::make()
            ->from(new \Article\Model\Orm\Article())
            ->where("alias like '#menu_%'")
            ->exec()
            ->fetchAll();
        
        foreach($linked_articles as $item) {
            if (preg_match('/#menu_(\d+)/', $item['alias'], $match)) {
                \RS\Orm\Request::make()
                    ->update(new \Menu\Model\Orm\Menu())
                    ->set(array('content' => $item['content']))
                    ->where(array(
                        'id' => $match[1]
                    ))->exec();
            }
        }
        
    }

    /**
     * Добавляем отсутствующее поле
     */
    function beforeUpdate20035()
    {
        $menu = new \Menu\Model\Orm\Menu();
        $menu->getPropertyIterator()->append(array(
            'link_template' => new \RS\Orm\Type\Template(array(
                'description' => t('Шаблон'),
                'visible' => false
            )),
        ));
        $menu->dbUpdate();        
    }

    /**
     * Добавляем отсутствующее поле
     */
    function beforeUpdate20023() 
    {
        $menu = new \Menu\Model\Orm\Menu();
        $menu->getPropertyIterator()->append(array(
            'target_blank' => new \RS\Orm\Type\Integer(array(
                'maxLength' => 1,
                'description' => t('Открывать ссылку в новом окне'),
                'checkboxView' => array(1,0),
                'default' => 0,
                'visible' => false
            ))
        ));
        $menu->dbUpdate();
    }
}

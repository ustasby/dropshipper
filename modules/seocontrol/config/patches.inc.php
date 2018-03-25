<?php
namespace SeoControl\Config;

class Patches extends \RS\Module\AbstractPatches
{
    /**
    * Возвращает массив имен патчей.
    */
    function init()
    {
        return array(
            '20015',
        );
    }
    
    function afterUpdate20015()
    {
        \RS\Orm\Request::make()
            ->update(new \SeoControl\Model\Orm\SeoRule())
            ->set('sortn = id')
            ->where(array(
                'sortn' => null
            ))->exec();
    }
}

<?php
namespace Evasmart\Controller\Front;

/**
* Фронт контроллер
*/
class Ctrl extends \RS\Controller\Front
{
    function actionIndex()
    {
        return $this->result->setTemplate('test.tpl');
    }
}
?>
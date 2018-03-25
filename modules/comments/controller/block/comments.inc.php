<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Comments\Controller\Block;
use \RS\Orm\Type;

/**
* Блок комментариев
* @ingroup Comments
*/
class Comments extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Комментарии',
        $controller_description = 'Отображает список комментариев к объекту. Позволяет добавить комментарий пользователю';
            
    protected
        $default_params = array(
            'indexTemplate' => 'blocks/comments/comment_block.tpl',
            'listTemplate'  => 'blocks/comments/list.tpl',
            'pageSize'      => 10
        ),      
        $action_var = 'cmdo',
        $type,
        $aid,
        $config,
        
        $sortby,
        $page,
        $pageSize,
        $api;
 
    function __construct($param = array())
    {
       
        parent::__construct($param);
         
        $this->api = new \Comments\Model\Api();
        $this->api->replaceByIp(true);

        $this->type = $this->getParam('type');
        $this->pageSize = $this->getParam('pageSize');
        
        $this->page = $this->url->get('cp', TYPE_INTEGER, 1);
        if ($this->page<1) $this->page = 1;        
        
        $this->config = \RS\Config\Loader::byModule($this);              
    }
        
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'listTemplate' => new Type\Template(array(
                'description' => t('Шаблон списка')
            )),
            'type' => new Type\Varchar(array(
                'description' => t('Тип комментариев'),
                'list' => array(array('\Comments\Model\Api', 'getTypeList'))
            )),
            'pageSize' => new Type\Integer(array(
                'description' => t('Количество комментариев на странице')
            ))
        ));
    }
    
    function checkType()
    {
       //Проверяем класс, указанный в типе комментария, на соответствие интерфейсу.
        if (!class_exists($this->type) 
                || !is_subclass_of($this->type, '\Comments\Model\Abstracttype')) {
            return $this->comError(t('Параметр: type должен содержать имя класса, наследника \Comments\Model\Abstracttype'));
        }
        $type_instance = new $this->type();
        $this->aid = $this->myGet('aid', TYPE_STRING, $type_instance->getLinkId());

        return true;
    }
    
    function actionIndex()
    {
        if (($error = $this->checkType()) !== true) return $error;
        if ($this->checkType()===true && $this->aid === false) return false;
             
        if ($this->isMyPost() && ($this->config['need_authorize'] == 'N' || \RS\Application\Auth::isAuthorize()))
        {
            $elem = $this->api->getElement();
            $elem['aid'] = $this->aid;
            $elem['type'] = $this->type;
            $elem['replace_by_ip'] = !$this->config['allow_more_comments'];
            $elem['dateof'] = date('Y-m-d H:i:s');

            //Если пользователь не авторизован, то проверяем капчу
            if (!\RS\Application\Auth::isAuthorize()) {
                $elem['__captcha']->setEnable(true);
            }

            if (\RS\Application\Auth::isAuthorize()) {
                $user = \RS\Application\Auth::getCurrentUser();
                $elem['user_id'] = $user['id'];
            }

            $elem->excludePostKeys(array('site_id', 'type', 'aid', 'dateof', 'user_id', 'moderated',
                                            'help_yes', 'help_no', 'ip', 'useful'));

            if ($this->api->save()) {
                $this->refreshPage();
            } else {
                $this->view->assign('error', $this->api->getElement()->getErrors());
            }
        } else {
            $comment = $this->api->getElement();
            $comment['rate'] = 5;
            if (\RS\Application\Auth::isAuthorize()) {
                $user = \RS\Application\Auth::getCurrentUser();
                $comment['user_name'] = $user['name'];
            }
        }
        $already_write = $this->api->alreadyWrite($this->aid);
        
        if ($this->config['allow_more_comments']){  //Если разрешена запись с одного IP нескольких комментариев
           $already_write = false; 
        }
        
        $this->view->assign(array(
            'already_write' => $already_write,
            'aid' => $this->aid,
            'comment' => $this->api->getElement(),
            'mod_config' => $this->config,
            'list_html' => $this->actionGetCommentList()->getHtml()
        ));
        
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
    
    function actionGetCommentList()
    {
        $this->checkType();
        
        $this->api
            ->setFilter('type', $this->type)
            ->setFilter('aid', $this->aid);
    
        if ($this->config['need_moderate'] == 'Y') {
            //Отображаем только проверенные
            $this->api->setFilter('moderated', 1);
        }
		

        $total = $this->api->getListCount();
        $this->api->joinVoteInfo();
        $paginator = new \RS\Helper\Paginator($this->page, $total, $this->pageSize, '?cp=%PAGE%');        
        $list = $this->api->getList($this->page, $this->pageSize);        

        $this->view->assign(array(
            'commentlist' => $list,
            'total' => $total,
            'paginator' => $paginator,          
        ));
        
        return $this->result->setTemplate( $this->getParam('listTemplate') );
    }
}

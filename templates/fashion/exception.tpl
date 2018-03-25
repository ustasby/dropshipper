<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Ой, ошибочка {$error.code}</title>
<!--[if lt IE 9]>
<script src="{$THEME_JS}/html5shiv.js"></script>
<![endif]-->
<link rel="stylesheet" type="text/css" href="{$THEME_CSS}/reset.css">
<link rel="stylesheet" type="text/css" href="{$THEME_CSS}/style.css">
{if $THEME_SHADE !== 'orange'}
<link rel="stylesheet" type="text/css" href="{$THEME_CSS}/{$THEME_SHADE}.css">
{/if}

</head>
<body class="exceptionBody">
<div class="bodyWrap">
    <header>
        <div class="viewport">
            {* Меню *}
            {moduleinsert name="\Menu\Controller\Block\Menu"}
            
            {* Логотип *}
            {moduleinsert name="\Main\Controller\Block\Logo" width="200" height="75"}
            
            <div class="userBlock">
                {if ModuleManager::staticModuleExists('shop')}
                    {* Блок авторизации *}
                    {moduleinsert name="\Users\Controller\Block\AuthBlock"}
                {/if}
                
                {* Поисковая строка *}
                {moduleinsert name="\Catalog\Controller\Block\SearchLine"}               
            </div>
        </div>
    </header>
    <div class="hotLinks viewport">
        <div class="links">
            <a href="/payment/" class="howToPay">Как оплатить</a>
            <a href="/delivery/" class="howToShip">Как получить</a>
        </div>
    </div>
    <div class="viewport mainContent">
        {* Список категорий товаров *}
        {moduleinsert name="\Catalog\Controller\Block\Category"}
        
        <div class="exception">
            <div class="code">
                <div class="number">{$error.code}</div>
                <div class="text">Страница не найдена</div>
            </div>
            <div class="message">{$error.comment}
            <br>
            <br>
            <a href="{$site->getRootUrl()}">Перейти на главную</a>
            </div>
            <div class="clearBoth"></div>                    
        </div>
      
        <footer>
            <div class="footzone">
                {moduleinsert name="\Menu\Controller\Block\Menu" indexTemplate="blocks/menu/foot_menu.tpl" root="bottom"}
                {if $CONFIG.facebook_group || $CONFIG.vkontakte_group || $CONFIG.twitter_group}
                <div class="social">
                    {if $CONFIG.facebook_group}
                    <a href="{$CONFIG.facebook_group}" class="facebook"></a>
                    {/if}
                    {if $CONFIG.vkontakte_group}
                    <a href="{$CONFIG.vkontakte_group}" class="vk"></a>
                    {/if}
                    {if $CONFIG.twitter_group}
                    <a href="{$CONFIG.twitter_group}" class="twitter"></a>
                    {/if}
                </div>
                {/if}
            </div>
            <div class="copyline">
                <a href="http://readyscript.ru" target="_blank" class="developer">Работает на <span>ReadyScript</span></a>            
                <span class="copy">&copy; {"now"|dateformat:"Y"} Все права защищены</span>
            </div>
        </footer>
    </div>
</div> <!-- .bodyWrap -->


</html>
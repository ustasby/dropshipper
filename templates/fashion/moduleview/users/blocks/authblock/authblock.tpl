{if $is_auth}
<div class="authorized">
    <a href="{$router->getUrl('users-front-profile')}" class="auth"></a>
    <ul class="dropdown">
        <li class="corner"></li>
        <li class="userInfo">
            {hook name="users-blocks-authblock:username" title="{t}Блок авторизации:имя пользователя{/t}"}
                {$current_user.name} {$current_user.surname}
            {/hook}
            <br>
            {if $use_personal_account}
            <span class="balance">Баланс:&nbsp;{hook name="users-blocks-authblock:balance" title="{t}Блок авторизации:баланс{/t}"}<a href="{$router->getUrl('shop-front-mybalance')}">{$current_user->getBalance(true, true)}</a>{/hook}</span>
            {/if}            
        </li>
        {hook name="users-blocks-authblock:cabinet-menu-items" title="{t}Блок авторизации:пункты меню личного кабинета{/t}"}
            <li class="item"><a href="{$router->getUrl('users-front-profile')}">Профиль</a></li>        
            <li class="item"><a href="{$router->getUrl('shop-front-myorders')}">Мои заказы</a></li>
            {if $use_personal_account}
            <li class="item"><a href="{$router->getUrl('shop-front-mybalance')}">Лицевой счет</a></li>
            {/if}
        {/hook}
        <li class="item"><a href="{$router->getUrl('users-front-auth', ['Act' => 'logout'])}">Выход</a></li>
    </ul>
</div>
{else}
<div class="auth alignright">
    {assign var=referer value=urlencode($url->server('REQUEST_URI'))}
    <a href="{$router->getUrl('users-front-auth', ['referer' => $referer])}" class="auth inDialog" title="Войти или зарегистрироваться"></a>
</div>
{/if}
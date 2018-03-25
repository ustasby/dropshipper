{if $is_auth}
<div class="personal">
    <p class="username rs-parent-switcher">{hook name="users-blocks-authblock:username" title="{t}Блок авторизации:имя пользователя{/t}"}{$current_user.name} {$current_user.surname}{/hook} <i class="down"></i></p>
    {if $use_personal_account}
    <p class="balance">{t}Баланс{/t}: {hook name="users-blocks-authblock:balance" title="{t}Блок авторизации:баланс{/t}"}<a class="underline" href="{$router->getUrl('shop-front-mybalance')}">{$current_user->getBalance(true, true)}</a>{/hook}</p>
    {/if}
    <ul class="userMenu">
        {hook name="users-blocks-authblock:cabinet-menu-items" title="{t}Блок авторизации:пункты меню личного кабинета{/t}"}
            <li><a href="{$router->getUrl('users-front-profile')}">{t}Профиль{/t}</a></li>
            <li><a href="{$router->getUrl('shop-front-myorders')}">{t}Мои заказы{/t}</a></li>
            {if $return_enable}
                <li><a href="{$router->getUrl('shop-front-myproductsreturn')}">{t}Мои возвраты{/t}</a></li>
            {/if}
            {if $use_personal_account}
                <li><a href="{$router->getUrl('shop-front-mybalance')}">{t}Лицевой счет{/t}</a></li>
            {/if}
        {/hook}
        <li><a href="{$router->getUrl('users-front-auth', ['Act' => 'logout'])}">{t}Выход{/t}</a></li>
    </ul>
</div>
{else}
<div class="guest">
    {assign var=referer value=urlencode($url->server('REQUEST_URI'))}
    <a href="{$router->getUrl('users-front-auth', ['referer' => $referer])}" class="join inDialog">{t}Войти{/t}</a>
    <a href="{$router->getUrl('users-front-register', ['referer' => $referer])}" class="reg inDialog">{t}Зарегистрироваться{/t}</a>
</div>
{/if}
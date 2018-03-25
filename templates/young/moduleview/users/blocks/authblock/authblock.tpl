{if $is_auth}
<div class="sign">
    {assign var=referer value=urlencode($url->server('REQUEST_URI'))}
    {hook name="users-blocks-authblock:username" title="{t}Блок авторизации:имя пользователя{/t}"}
        <a href="{$router->getUrl('users-front-profile')}" class="auth">{$current_user.name} {$current_user.surname}</a>
    {/hook}
    {if $use_personal_account}
        {hook name="users-blocks-authblock:balance" title="{t}Блок авторизации:баланс{/t}"}
            <a href="{$router->getUrl('shop-front-mybalance')}" class="register">{t}Баланс{/t} {$current_user->getBalance(true, true)}</a>
        {/hook}
    {/if}
</div>
{else}
<div class="sign">
    <div class="icon"></div>
    {assign var=referer value=urlencode($url->server('REQUEST_URI'))}
    <a href="{$router->getUrl('users-front-auth', ['referer' => $referer])}" class="auth inDialog">{t}Вход{/t}</a>
    <a href="{$router->getUrl('users-front-register', ['referer' => $referer])}" class="register inDialog">{t}Регистрация{/t}</a>
</div>
{/if}
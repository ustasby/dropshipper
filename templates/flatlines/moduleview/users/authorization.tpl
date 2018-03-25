{* Диалог авторизации пользователя *}
{$is_dialog_wrap=$url->request('dialogWrap', $smarty.const.TYPE_INTEGER)}
<div class="form-style modal-body mobile-width-small">
    {if $is_dialog_wrap}
        <h2 class="h2">{t}Авторизация{/t}</h2>
    {/if}

    {if !empty($status_message)}<div class="page-error">{$status_message}</div>{/if}

    <form method="POST" action="{$router->getUrl('users-front-auth')}">
        {hook name="users-authorization:form" title="{t}Авторизация:форма{/t}"}
        {$this_controller->myBlockIdInput()}
        <input type="hidden" name="referer" value="{$data.referer}">
        <input type="hidden" name="remember" value="1" checked>

        <input type="text" placeholder="{t}Введите свой E-mail{/t}" name="login" value="{$data.login|default:$Setup.DEFAULT_DEMO_LOGIN}" {if !empty($error)}class="has-error"{/if} autocomplete="off">
        {if $error}<span class="formFieldError">{$error}</span>{/if}

        <input type="password" placeholder="{t}Введите пароль{/t}" name="pass" value="{$Setup.DEFAULT_DEMO_PASS}" {if !empty($error)}class="has-error"{/if} autocomplete="off">

        <div class="form__menu_buttons mobile-flex">
            <button type="submit" class="link link-more">{t}Войти{/t}</button>

            <div>
                {if $is_dialog_wrap}
                    <a href="{$router->getUrl('users-front-register')}" class="rs-in-dialog">{t}Зарегистрироваться{/t}</a><br>
                {/if}

                <a href="{$router->getUrl('users-front-auth', ["Act" => "recover"])}" {if $is_dialog_wrap}class="rs-in-dialog"{/if}>{t}Забыли пароль?{/t}</a>
            </div>
        </div>
        {/hook}
    </form>
</div>
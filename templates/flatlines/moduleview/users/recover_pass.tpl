{* Страница восстановления пароля *}

{$is_dialog_wrap=$url->request('dialogWrap', $smarty.const.TYPE_INTEGER)}
{hook name="users-authorization:form" title="{t}Авторизация:форма{/t}"}
    <div class="form-style modal-body mobile-width-small">
        {if $is_dialog_wrap}
            <h2 class="h2">{t}Восстановление пароля{/t}</h2>
        {/if}
        <form method="POST" action="{$router->getUrl('users-front-auth', ["Act" => "recover"])}">
            {$this_controller->myBlockIdInput()}
            <input type="hidden" name="referer" value="{$data.referer}">
            <input type="hidden" name="remember" value="1" checked>

            <input type="text" placeholder="{t}Введите свой E-mail{/t}" name="login" value="{$data.login}" {if !empty($error)}class="has-error"{/if} {if $send_success}readonly{/if}>
            {if $error}<span class="formFieldError">{$error}</span>{/if}

            {if $send_success}
                <p class="recover-text success">
                    {t}Письмо успешно отправлено. Следуйте инструкциям в письме{/t}
                </p>
            {else}
                <p class="recover-text">
                    {t}На указанный E-mail будет отправлено письмо с дальнейшими инструкциями по восстановлению пароля{/t}
                </p>
                <div class="form__menu_buttons">
                    <button type="submit" class="link link-more">{t}Восстановить{/t}</button>
                </div>
            {/if}
        </form>
    </div>
{/hook}
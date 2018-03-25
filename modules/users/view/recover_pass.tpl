<form method="POST" action="{$router->getUrl('users-front-auth', ["Act" => "recover"])}" class="authorization">
    {$this_controller->myBlockIdInput()}
    {if $url->request('dialogWrap', $smarty.const.TYPE_INTEGER)}
        <h2 data-dialog-options='{ "width": "360" }'>{t}Восстановление пароля{/t}</h2>
        <div class="dialogForm">
            {if !empty($error)}<div class="error">{$error}</div>{/if}
            <input type="text" name="login" value="{$data.login}" placeholder="e-mail" class="login" value="{$data.login}" {if $send_success}readonly{/if}>
            {if $send_success}
                <div class="recoverText success">
                    <i></i>
                    {t}Письмо успешно отправлено. Следуйте инструкциям в письме{/t}
                </div>            
            {else}
                <div class="recoverText">
                    <i></i>
                    {t}На указанный E-mail будет отправлено письмо с дальнейшими инструкциями по восстановлению пароля{/t}
                </div>
                <div class="floatWrap">
                    <button type="submit">{t}Отправить{/t}</button>
                </div>
            {/if}
            
            <div class="noAccount">
                {t}Нет аккаунта?{/t} &nbsp;&nbsp;&nbsp;<a href="{$router->getUrl('users-front-register')}" class="inDialog">{t}Зарегистрируйтесь{/t}</a><br>
                {t}Вспомнили пароль?{/t} &nbsp;&nbsp;&nbsp;<a href="{$router->getUrl('users-front-auth')}" class="inDialog">{t}Авторизуйтесь{/t}</a>
            </div>
        </div>
    {else}
        {if $send_success}
            <div class="formSuccessText">E-mail: {$data.login}<br>
                {t}Письмо успешно отправлено. Следуйте инструкциям в письме{/t}</div>
        {else}
        <table class="formTable">
            <tr>
                <td class="key">E-mail</td>
                <td class="value"><input type="text" size="30" name="login" value="{$data.login}" {if !empty($error)}class="has-error"{/if}>
                <span class="formFieldError">{$error}</span>
                <div class="help">{t}На указанный E-mail будет отправлено письмо с дальнейшими инструкциями по восстановлению пароля{/t}</div>
                </td>
            </tr>
        </table>
        <button type="submit" class="formSave">{t}Отправить{/t}</button>
        {/if}
    {/if}
</form>
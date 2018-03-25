<form method="POST" action="{$router->getUrl('users-front-auth')}" class="authorization formStyle">
    <h1 data-dialog-options='{ "width": "460" }'>Авторизация</h1>
    {$this_controller->myBlockIdInput()}
    {hook name="users-authorization:form" title="{t}Авторизация:форма{/t}"}
        <div class="forms">
            <input type="hidden" name="referer" value="{$data.referer}">
            {if !empty($status_message)}<div class="pageError">{$status_message}</div>{/if}
            {if !empty($error)}<div class="error">{$error}</div>{/if}
            <div class="center">
                <div class="formLine">
                    <label class="fieldName">E-mail</label>
                    <input type="text" size="30" name="login" value="{$data.login|default:$Setup.DEFAULT_DEMO_LOGIN}" class="inp">
                </div>
                <div class="formLine">
                    <label class="fieldName">Пароль</label>
                    <input type="password" size="30" name="pass" value="{$Setup.DEFAULT_DEMO_PASS}" class="inp">
                </div>    
                <div class="formLine rem">
                    <input type="checkbox" id="rememberMe" name="remember" value="1" {if $data.remember}checked{/if}> <label for="rememberMe">Запомнить меня</label>
                </div>
                <div class="oh">
                    <div class="fleft">
                        <input type="submit" value="Войти">                        
                    </div>
                    <div class="fright">
                        <a href="{$router->getUrl('users-front-auth', ["Act" => "recover"])}" class="recover inDialog">Забыли пароль?</a>
                    </div>
                </div>
            </div>
            <div class="underLine">
                <p>Зарегистрировавшись у нас, Вы сможете хранить всю информацию о Ваших покупках, адресах доставок на нашем сайте, 
                    а также видеть ход исполнения заказов. Регистрация займет не более 2-х минут.</p>
                <a href="{$router->getUrl('users-front-register')}" class="button color reg inDialog">Зарегистрироваться</a>
            </div>
        </div>
    {/hook}
</form>
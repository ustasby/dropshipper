<form method="POST" action="{$router->getUrl('users-front-auth')}" class="authorization formStyle">
    {$this_controller->myBlockIdInput()}
    {hook name="users-authorization:form" title="{t}Авторизация:форма{/t}"}
        <div class="forms">
            <input type="hidden" name="referer" value="{$data.referer}">
            <h2 data-dialog-options='{ "width": "450" }'>Войти</h2>
            {if !empty($status_message)}<div class="pageError pbottom">{$status_message}</div>{/if}
            {if !empty($error)}<div class="error">{$error}</div>{/if}
            <div class="center">
                <div class="formLine">
                    <label class="fielName">E-mail</label><br>
                    <input type="text" size="30" name="login" value="{$data.login|default:$Setup.DEFAULT_DEMO_LOGIN}" class="inp">
                </div>
                <div class="formLine">
                    <label class="fielName">{t}Пароль{/t}</label><br>
                    <input type="password" size="30" name="pass" value="{$Setup.DEFAULT_DEMO_PASS}" class="inp">
                </div>    
                <div class="formLine rem">
                    <input type="checkbox" id="rememberMe" name="remember" value="1" {if $data.remember}checked{/if}> <label for="rememberMe">{t}Запомнить меня{/t}</label>
                </div>
                <div class="oh">
                    <div class="fleft">
                        <input type="submit" value="Войти">
                    </div>
                    <div class="fright">
                        <a href="{$router->getUrl('users-front-auth', ["Act" => "recover"])}" class="recover inDialog">{t}Забыли пароль?{/t}</a>
                    </div>
                </div>
            </div>
        </div>        
        <div class="grayBlock">
            <i class="lines"></i>
            <a href="{$router->getUrl('users-front-register')}" class="reg inDialog">{t}Зарегистрироваться{/t}</a>
        </div>
    {/hook}
</form>
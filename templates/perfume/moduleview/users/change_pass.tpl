<form method="POST" class="authorization formStyle">
    <div class="forms">
        <h2>{t}Восстановление пароля{/t}</h2>
        <div class="center">
            <div class="formLine">
                <label class="fielName">{t}Новый пароль{/t}</label><br>
                <input type="password" size="30" name="new_pass" class="inp{if !empty($error)} has-error{/if}">
                <span class="formFieldError">{$error}</span>
            </div>
            <div class="formLine">
                <label class="fielName">{t}Повтор нового пароля{/t}</label><br>
                <input type="password" size="30" name="new_pass_confirm" class="inp">
            </div>            
            <input type="submit" value="{t}Сменить пароль{/t}">
        </div>
    </div>
</form>
<form method="POST" class="authorization formStyle">
    <h1>Восстановление пароля</h1>
    <div class="forms">
        <div class="center">
            <div class="formLine">
                <label class="fieldName">Новый пароль</label>
                <input type="password" size="30" name="new_pass" class="inp{if !empty($error)} has-error{/if}">
                <span class="formFieldError">{$error}</span>
            </div>
            <div class="formLine">
                <label class="fieldName">Повтор нового пароля</label>
                <input type="password" size="30" name="new_pass_confirm" class="inp">
            </div>            
            <input type="submit" value="Сменить пароль">
        </div>
    </div>
</form>
{if count($user->getNonFormErrors())>0}
    <div class="pageError">
        {foreach $user->getNonFormErrors() as $item}
        <p>{$item}</p>
        {/foreach}
    </div>
{/if}    

{if $result}
    <div class="formResult success">{$result}</div>
{/if}

<form method="POST" class="formStyle profile">
    {csrf}
    {$this_controller->myBlockIdInput()}
    <div class="userType">
        <input type="radio" id="ut_user" name="is_company" value="0" {if !$user.is_company}checked{/if}><label for="ut_user">{t}Частное лицо{/t}</label>
        <input type="radio" id="ut_company" name="is_company" value="1" {if $user.is_company}checked{/if}><label for="ut_company">{t}Компания{/t}</label>
    </div>    
    <div class="oh">
        <div class="half fleft{if $user.is_company} thiscompany{/if}" id="fieldsBlock">
            <div class="companyFields">
                <div class="formLine">
                    <label class="fielName">{t}Название организации{/t}</label><br>
                    {$user->getPropertyView('company')}
                </div>                            
                <div class="formLine">
                    <label class="fielName">{t}ИНН{/t}</label><br>
                    {$user->getPropertyView('company_inn')}
                </div>                                
            </div>
            <div class="formLine">
                <label class="fielName">{t}Фамилия{/t}</label><br>
                {$user->getPropertyView('surname')}
            </div>                    
            <div class="formLine">
                <label class="fielName">{t}Имя{/t}</label><br>
                {$user->getPropertyView('name')}
            </div>
            <div class="formLine">
                <label class="fielName">{t}Отчество{/t}</label><br>
                {$user->getPropertyView('midname')}
            </div>
            <div class="formLine">
                <label class="fielName">{t}Телефон{/t}</label><br>
                {$user->getPropertyView('phone')}
            </div>
            <div class="formLine">
                <label class="fielName">E-mail</label><br>
                {$user->getPropertyView('e_mail')}
            </div>
            {if $conf_userfields->notEmpty()}
                {foreach $conf_userfields->getStructure() as $fld}
                <div class="formLine">
                <label class="fielName">{$fld.title}</label><br>
                    {$conf_userfields->getForm($fld.alias)}
                    {$errname=$conf_userfields->getErrorForm($fld.alias)}
                    {$error=$user->getErrorsByForm($errname, ', ')}
                    {if !empty($error)}
                        <span class="formFieldError">{$error}</span>
                    {/if}
                </div>
                {/foreach}
            {/if}                 
            
        </div>
        <div class="half fright">
            <div class="formLine alignRight">
                <br>
                <span class="inputHeight">
                    <label for="changepass">{t}Сменить пароль{/t}</label><input type="checkbox" name="changepass" id="changepass" value="1" {if $user.changepass}checked{/if}>
                </span>
            </div>
            <div class="changePass {if !$user.changepass}hidden{/if}">
                <div class="formLine">
                    <label class="fielName">{t}Текущий пароль{/t}</label><br>
                    <input type="password" name="current_pass" {if count($user->getErrorsByForm('current_pass'))}class="has-error"{/if}>
                    <span class="formFieldError">{$user->getErrorsByForm('current_pass', ',')}</span>
                </div>
                <div class="formLine">
                    <label class="fielName">{t}Новый пароль{/t}</label><br>
                    <input type="password" name="openpass" {if count($user->getErrorsByForm('openpass'))}class="has-error"{/if}>
                    <span class="formFieldError">{$user->getErrorsByForm('openpass', ',')}</span>
                </div>                        
                <div class="formLine">
                    <label class="fielName">{t}Повторить пароль{/t}</label><br>
                    <input type="password" name="openpass_confirm" {if count($user->getErrorsByForm('openpass'))}class="has-error"{/if}>
                </div>
            </div>
        </div>
    </div>
    <div class="buttons cboth">
        <input type="submit" value="{t}Сохранить{/t}">
    </div>    
</form>

    <script type="text/javascript">
        $(function() {
            $('#changepass').change(function() {
                $('.changePass').toggleClass('hidden', !this.checked);
            });            
            
            $('.profile .userType input').click(function() {
                $('#fieldsBlock').toggleClass('thiscompany', $(this).val() == 1);
            });
        });        
    </script>
<form method="POST" action="{$router->getUrl('users-front-register')}" class="authorization register formStyle">
    <input type="hidden" name="referer" value="{$referer}">
    {$this_controller->myBlockIdInput()}
    <h2 data-dialog-options='{ "width": "755" }'>{t}Регистрация пользователя{/t}</h2>
        {if count($user->getNonFormErrors())>0}
            <div class="pageError">
                {foreach $user->getNonFormErrors() as $item}
                <p>{$item}</p>
                {/foreach}
            </div>
        {/if}
        {hook name="users-registers:form" title="{t}Регистрация:форма{/t}"}    
            <div class="forms">                
            <div class="userType">
                <input type="radio" id="ut_user" name="is_company" value="0" {if !$user.is_company}checked{/if}><label for="ut_user">{t}Частное лицо{/t}</label>
                <input type="radio" id="ut_company" name="is_company" value="1" {if $user.is_company}checked{/if}><label for="ut_company">{t}Компания{/t}</label>
            </div>
            <div class="oh {if $user.is_company} thiscompany{/if}" id="fieldsBlock">
                <div class="half fleft">
                    <div class="companyFields">
                        <div class="formLine">
                            <label class="fielName">{t}Название организации{/t}</label><br>
                            {$user->getPropertyView('company')}
                        </div>                            
                    </div>
                    <div class="formLine">
                        <label class="fielName">{t}Имя{/t}</label><br>
                        {$user->getPropertyView('name')}
                    </div>            
                    <div class="formLine">
                        <label class="fielName">{t}Фамилия{/t}</label><br>
                        {$user->getPropertyView('surname')}
                    </div>
                    <div class="formLine">
                        <label class="fielName">{t}Отчество{/t}</label><br>
                        {$user->getPropertyView('midname')}
                    </div>
                    <div class="formLine">
                        <label class="fielName">{t}Телефон{/t}</label><br>
                        {$user->getPropertyView('phone')}
                    </div>
                    <div class="formLine captcha">
                        <label class="fielName">&nbsp;</label><br>
                        <div class="alignLeft">
                            {$user->getPropertyView('captcha')}
                            <br><span class="fielName">{$user->__captcha->getTypeObject()->getFieldTitle()}</span>
                        </div>
                    </div>
                </div>
                <div class="half fright">
                    <div class="companyFields">
                        <div class="formLine">
                            <label class="fielName">{t}ИНН{/t}</label><br>
                            {$user->getPropertyView('company_inn')}
                        </div>
                    </div>
                    <div class="formLine">
                        <label class="fielName">E-mail</label><br>
                        {$user->getPropertyView('e_mail')}
                    </div>
                    <div class="formLine">
                        <label class="fielName">{t}Пароль{/t}</label><br>
                        <input type="password" name="openpass" {if count($user->getErrorsByForm('openpass'))}class="has-error"{/if}>
                        <div class="formFieldError">{$user->getErrorsByForm('openpass', ',')}</div>                    
                    </div>            
                    <div class="formLine">
                        <label class="fielName">{t}Повтор пароля{/t}</label><br>
                        <input type="password" name="openpass_confirm">
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
            </div>
            <div class="buttons cboth">
                {if $CONFIG.enable_agreement_personal_data}
                    {include file="%site%/policy/agreement_phrase.tpl" button_title="{t}Зарегистрироваться{/t}"}
                {/if}
                <input type="submit" value="{t}Зарегистрироваться{/t}">
                <br><br>
            </div> 
        {/hook}
    </div>   
    
    <script type="text/javascript">
        $(function() {
            $('.userType input').click(function() {
                $('#fieldsBlock').toggleClass('thiscompany', $(this).val() == 1);
                if ($(this).closest('#colorbox')) $.colorbox.resize();
            });
        });        
    </script>    
</form>
{* Регистрация пользователя *}

{$is_dialog_wrap=$url->request('dialogWrap', $smarty.const.TYPE_INTEGER)}
<div class="form-style modal-body mobile-width-wide">

    {if $is_dialog_wrap}
        <h2 class="h2">{t}Регистрация{/t}</h2>
    {/if}

    {if count($user->getNonFormErrors())>0}
        <div class="page-error">
            {foreach $user->getNonFormErrors() as $item}
                <div class="item">{$item}</div>
            {/foreach}
        </div>
    {/if}

    {if $result}
        <div class="page-success-result">{$result}</div>
    {/if}

    <form method="POST" action="{$router->getUrl('users-front-register')}">
        {csrf}
        {$this_controller->myBlockIdInput()}
        <input type="hidden" name="referer" value="{$referer}">

            {hook name="users-registers:form" title="{t}Регистрация:форма{/t}"}
                <div class="form-group">
                    <input type="radio" name="is_company" value="0" id="is_company_no" {if !$user.is_company}checked{/if}>&nbsp;<label for="is_company_no">{t}Частное лицо{/t}</label><br>
                    <input type="radio" name="is_company" value="1" id="is_company_yes" {if $user.is_company}checked{/if}>&nbsp;<label for="is_company_yes">{t}Юридическое лицо или ИП{/t}</label>
                </div>

            <div class="mobile-2-column">
                <div class="form-fields_company{if !$user.is_company} hidden{/if}">
                    <div class="form-group">
                        <label class="label-sup">{t}Наименование компании{/t}</label>
                        {$user->getPropertyView('company', ['placeholder' => "{t}Например, ООО Ромашка{/t}"])}
                    </div>
                    <div class="form-group">
                        <label class="label-sup">{t}ИНН{/t}</label>
                        {$user->getPropertyView('company_inn', ['placeholder' => "{t}10 или 12 цифр{/t}"])}
                    </div>
                </div>

                <div class="form-group">
                    <label class="label-sup">{t}Имя{/t}</label>
                    {$user->getPropertyView('name', ['placeholder' => "{t}Например, Иван{/t}"])}
                </div>

                <div class="form-group">
                    <label class="label-sup">{t}Фамилия{/t}</label>
                    {$user->getPropertyView('surname', ['placeholder' => "{t}Например, Иванов{/t}"])}
                </div>

                <div class="form-group">
                    <label class="label-sup">{t}Отчество{/t}</label>
                    {$user->getPropertyView('midname', ['placeholder' => "{t}Например, Иванович{/t}"])}
                </div>

                <div class="form-group">
                    <label class="label-sup">{t}Телефон{/t}</label>
                    {$user->getPropertyView('phone', ['placeholder' => "{t}Например, +7(XXX)-XXX-XX-XX{/t}"])}
                </div>

                <div class="form-group">
                    <label class="label-sup">{t}E-mail{/t}</label>
                    {$user->getPropertyView('e_mail', ['placeholder' => "{t}Например, demo@example.com{/t}"])}
                </div>

                {if $conf_userfields->notEmpty()}
                    {foreach $conf_userfields->getStructure() as $fld}
                        <div class="form-group">
                            <label class="label-sup">{$fld.title}</label>
                            {$conf_userfields->getForm($fld.alias)}

                            {$errname = $conf_userfields->getErrorForm($fld.alias)}
                            {$error = $user->getErrorsByForm($errname, ', ')}
                            {if !empty($error)}
                                <span class="formFieldError">{$error}</span>
                            {/if}
                        </div>

                    {/foreach}
                {/if}
                
                <div class="form-group">
                    <label class="label-sup">{$user->__captcha->getTypeObject()->getFieldTitle()}</label>
                    {$user->getPropertyView('captcha')}
                </div>

                <div class="form-group">
                    <label class="label-sup">{t}Пароль{/t}</label>
                    {$user->getPropertyView('openpass')}
                </div>
                <div class="form-group">
                    <label class="label-sup">{t}Повтор пароля{/t}</label>
                    {$user->getPropertyView('openpass_confirm')}
                </div>
            </div>
            {/hook}

        {if $CONFIG.enable_agreement_personal_data}
            {include file="%site%/policy/agreement_phrase.tpl" button_title="{t}Зарегистрироваться{/t}"}
        {/if}

        <div class="form__menu_buttons mobile-center">


            <button type="submit" class="link link-more">{t}Зарегистрироваться{/t}</button>
        </div>
    </form>
</div>
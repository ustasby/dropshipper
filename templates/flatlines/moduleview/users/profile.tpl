{* Профиль пользователя *}

{addjs file="rs.profile.js"}
<div class="form-style">
    <div class="tab-content">
        <div id="menu1" class="tab-pane fade active in">
            <div class="col-xs-12">
                <h3 class="h3">{t}Личные данные{/t}</h3>

                {if $errors=$user->getNonFormErrors()}
                    <div class="page-error">
                        {foreach $errors as $item}
                            <div class="item">{$item}</div>
                        {/foreach}
                    </div>
                {/if}

                {if $result}
                    <div class="page-success-result">{$result}</div>
                {/if}

                <form method="POST">
                    {csrf}
                    {$this_controller->myBlockIdInput()}
                    <input type="hidden" name="referer" value="{$referer}">

                    <div class="form-group">
                        <input type="radio" name="is_company" value="0" id="is_company_no" {if !$user.is_company}checked{/if}><label for="is_company_no">{t}Частное лицо{/t}</label><br>
                        <input type="radio" name="is_company" value="1" id="is_company_yes" {if $user.is_company}checked{/if}><label for="is_company_yes">{t}Юридическое лицо или ИП{/t}</label>
                    </div>

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

                    <div class="form_label__block form-group">
                        <input type="checkbox" name="changepass" id="pass-accept" value="1" {if $user.changepass}checked{/if}>
                        <label for="pass-accept">{t}Сменить пароль{/t}</label>
                    </div>

                    <div class="form-fields_change-pass{if !$user.changepass} hidden{/if}">
                        <div class="form-group">
                            <label class="label-sup">{t}Старый пароль{/t}</label>
                            {$user->getPropertyView('current_pass')}
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

                    <div class="form__menu_buttons">
                        <button type="submit" class="link link-more">{t}Сохранить{/t}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
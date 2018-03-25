{* Оформление заказа. Шаг - Адрес *}

{addjs file="rs.order.js"}

<div class="page-registration-steps">
    <div class="t-registration-steps">

            {* Текущий шаг оформления заказа *}
            {moduleinsert name="\Shop\Controller\Block\CheckoutStep"}

            <div class="form-style">
                <form method="POST" class="t-order rs-order-form {$order.user_type|default:"authorized"}" data-city-autocomplete-url="{$router->getUrl('shop-front-checkout', ['Act'=>'searchcity'])}">

                    {if $errors=$order->getNonFormErrors()}
                        <div class="page-error">
                            {foreach $errors as $item}
                                <p>{$item}</p>
                            {/foreach}
                        </div>
                    {/if}

                    {if !$is_auth}
                    <ul class="nav nav-tabs hidden-xs hidden-sm rs-user-type-tabs">
                        <li {if $order.user_type == 'person'}class="active"{/if}><a data-toggle="tab" data-value="person">{t}Частное лицо{/t}</a></li>
                        <li {if $order.user_type == 'company'}class="active"{/if}><a data-toggle="tab" data-value="company">{t}Компания{/t}</a></li>
                        <li {if $order.user_type == 'noregister'}class="active"{/if}><a data-toggle="tab" data-value="noregister">{t}Без регистрации{/t}</a></li>
                        <li {if $order.user_type == 'user'}class="active"{/if}><a data-toggle="tab" data-value="user">{t}Я регистрировался ранее{/t}</a></li>
                    </ul>
                    {/if}

                    <div class="t-order_contact-information user-contacts">
                        <h3 class="h3">{t}Контактные данные{/t}</h3>
                            {if !$is_auth}
                                <div class="form-group visible-xs visible-sm">
                                    <input type="radio" id="type-user" name="user_type" value="person" {if $order.user_type=='person'}checked{/if}> <label for="type-user">{t}Частное лицо{/t}</label><br>
                                    <input type="radio" id="type-company" name="user_type" value="company" {if $order.user_type=='company'}checked{/if}> <label for="type-company">{t}Компания{/t}</label><br>
                                    <input type="radio" id="type-noregister" name="user_type" value="noregister" {if $order.user_type=='noregister'}checked{/if}> <label for="type-noregister">{t}Без регистрации{/t}</label><br>
                                    <input type="radio" id="type-account" name="user_type" value="user" {if $order.user_type=='user'}checked{/if}> <label for="type-account">{t}Я регистрировался ранее{/t}</label>
                                </div>
                            {/if}

                        {if $is_auth}
                            <table class="table-underlined">
                                {if $user.is_company}
                                    <tr class="table-underlined-text">
                                        <td><span>{t}Наименование компании{/t}</span></td>
                                        <td><span>{$user.company}</span></td>
                                    </tr>
                                    <tr class="table-underlined-text">
                                        <td><span>{t}ИНН{/t}</span></td>
                                        <td><span>{$user.company_inn}</span></td>
                                    </tr>
                                {/if}
                                <tr class="table-underlined-text">
                                    <td>{t}Имя{/t}</td>
                                    <td>{$user.name}</td>
                                </tr>
                                <tr class="table-underlined-text">
                                    <td>{t}Фамилия{/t}</td>
                                    <td>{$user.surname}</td>
                                </tr>
                                <tr class="table-underlined-text">
                                    <td>{t}Отчество{/t}</td>
                                    <td>{$user.midname}</td>
                                </tr>
                                <tr class="table-underlined-text">
                                    <td>{t}Телефон{/t}</td>
                                    <td>{$user.phone}</td>
                                </tr>
                                <tr class="table-underlined-text">
                                    <td>E-mail</td>
                                    <td>{$user.e_mail}</td>
                                </tr>
                            </table>
                            <div class="form-group changeUser">
                                <a href="{urlmake logout=true}" class="link link-white">{t}Сменить пользователя{/t}</a>
                            </div>
                        {else}
                            <div class="user-register">
                                <div class="organization">
                                    <div class="form-group">
                                        <label class="label-sup">{t}Наименование компании{/t}</label>
                                        {$order->getPropertyView('reg_company', ['placeholder' => "{t}Например: ООО Ромашка{/t}"])}
                                    </div>
                                    <div class="form-group">
                                        <label class="label-sup">{t}ИНН{/t}</label>
                                        {$order->getPropertyView('reg_company_inn', ['placeholder' => "{t}10 или 12 цифр{/t}"])}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="label-sup">{t}Имя{/t}</label>
                                    {$order->getPropertyView('reg_name', ['placeholder' => "{t}Имя покупателя, владельца аккаунта{/t}"])}
                                </div>
                                <div class="form-group">
                                    <label class="label-sup">{t}Фамилия{/t}</label>
                                    {$order->getPropertyView('reg_surname', ['placeholder' => "{t}Фамилия покупателя, владельца аккаунта{/t}"])}
                                </div>
                                <div class="form-group">
                                    <label class="label-sup">{t}Отчество{/t}</label>
                                    {$order->getPropertyView('reg_midname', ['placeholder' => "{t}Отчество покупателя, владельца аккаунта{/t}"])}
                                </div>
                                <div class="form-group">
                                    <label class="label-sup">{t}Телефон{/t}</label>
                                    {$order->getPropertyView('reg_phone', ['placeholder' => "{t}В формате: +7(123)9876543{/t}"])}
                                </div>
                                <div class="form-group">
                                    <label class="label-sup">{t}E-mail{/t}</label>
                                    {$order->getPropertyView('reg_e_mail')}
                                </div>

                                <div class="form-group">
                                    <label class="label-sup">{t}Пароль{/t}</label>

                                    <input type="checkbox" name="reg_autologin" {if $order.reg_autologin}checked{/if} value="1" id="reg-autologin">&nbsp;<label for="reg-autologin">{t}Получить автоматически на e-mail{/t}</label>
                                    <div class="help">{t}Нужен для проверки статуса заказа, обращения в поддержку, входа в кабинет{/t}</div>

                                    <div class="rs-manual-login" {if $order.reg_autologin}style="display:none"{/if}>
                                        <div class="inline">
                                            {$order->getPropertyView('reg_openpass', ['placeholder' => "{t}Пароль{/t}"])}
                                        </div>
                                        <div class="inline">
                                            {$order->getPropertyView('reg_pass2', ['placeholder' => "{t}Повтор пароля{/t}"])}
                                        </div>
                                    </div>
                                </div>

                                {foreach $reg_userfields->getStructure() as $fld}
                                    <div class="form-group">
                                        <label class="label-sup">{$fld.title}</label>
                                        {$reg_userfields->getForm($fld.alias)}
                                        {$errname=$reg_userfields->getErrorForm($fld.alias)}
                                        {$error=$order->getErrorsByForm($errname, ', ')}
                                        {if !empty($error)}
                                            <span class="formFieldError">{$error}</span>
                                        {/if}
                                    </div>
                                {/foreach}
                            </div>
                        {/if}

                        <div class="user-without-register">
                            <div class="form-group">
                                <label class="label-sup">{t}Ф.И.О.{/t}</label>
                                {$order->getPropertyView('user_fio', ['placeholder' => "{t}Фамилия, Имя и Отчество покупателя, владельца аккаунта{/t}"])}
                            </div>
                            <div class="form-group">
                                <label class="label-sup">{t}E-mail{/t}</label>
                                {$order->getPropertyView('user_email', ['placeholder' => "{t}E-mail покупателя, владельца аккаунта{/t}"])}
                            </div>
                            <div class="form-group">
                                <label class="label-sup">{t}Телефон{/t}</label>
                                {$order->getPropertyView('user_phone', ['placeholder' => "{t}В формате: +7(123)9876543{/t}"])}
                            </div>
                        </div>

                        <div class="rs-has-account">
                            <div class="workArea">
                                <h3 class="h3">{t}Вход{/t}</h3>

                                <input type="hidden" name="ologin" value="1" id="doAuth" {if $order.user_type!='user'}disabled{/if}>
                                <div class="form-group">
                                    <label class="label-sup">{t}E-mail{/t}</label>
                                    {$order->getPropertyView('login')}
                                </div>
                                <div class="form-group">
                                    <label class="label-sup">{t}Пароль{/t}</label>
                                    {$order->getPropertyView('password')}
                                </div>
                                <div class="form__menu_buttons">
                                    <button type="submit" class="link link-more">{t}Войти{/t}</button>
                                </div>
                            </div>
                        </div>

                        {if !$is_auth}
                            <div class="form-group captcha">
                                <label class="label-sup">{$order->__code->getTypeObject()->getFieldTitle()}</label>
                                {$order->getPropertyView('code')}
                            </div>
                        {/if}

                    </div>

                    {if $have_to_address_delivery}
                        <div class="t-order_contact-information">
                            <h3 class="h3">{t}Адрес{/t}</h3>
                            
                            {if $have_pickup_points}
                                <div class="formPickUpTypeWrapper">
                                    <input id="onlyPickUpPoints" type="radio" name="only_pickup_points" value="1" {if $order.only_pickup_points}checked{/if}/> <label for="onlyPickUpPoints">{t}Самовывоз{/t}</label><br/>
                                    <input id="onlyDelivery" type="radio" name="only_pickup_points" value="0" {if !$order.only_pickup_points}checked{/if}/> <label for="onlyDelivery">{t}Доставка по адресу{/t}</label>
                                </div>
                            {/if}

                            <div id="form-address-section-wrapper" class="{if $have_pickup_points && $order.only_pickup_points}hidden{/if}">

                                {if count($address_list)>0}
                                    <ul class="form-group last-address rs-last-address">
                                        {foreach $address_list as $address}
                                            <li class="item">
                                                <input type="radio" name="use_addr" value="{$address.id}" id="adr_{$address.id}" {if $order.use_addr == $address.id}checked{/if}><label for="adr_{$address.id}">{$address->getLineView()}</label>
                                                <a href="{$router->getUrl('shop-front-checkout', ['Act' =>'deleteAddress', 'id' => $address.id])}" class="rs-delete-address"><i class="pe-2x pe-va pe-7s-close"></i></a>
                                            </li>
                                        {/foreach}
                                        <li>
                                            <input type="radio" name="use_addr" value="0" id="use_addr_new" {if $order.use_addr == 0}checked{/if}><label for="use_addr_new">{t}Другой адрес{/t}</label>
                                        </li>
                                    </ul>
                                {else}
                                    <input type="hidden" name="use_addr" value="0">
                                {/if}

                                <div class="rs-new-address{if $order.use_addr>0 && $address_list} hide{/if}">
                                    <div class="form-group">
                                        <label class="label-sup">{t}Страна{/t}</label>
                                        {$region_tools_url=$router->getUrl('shop-front-regiontools', ["Act" => 'listByParent'])}
                                        {$order->getPropertyView('addr_country_id', ['data-region-url' => $region_tools_url])}
                                    </div>
                                    <div class="form-group">
                                        <label class="label-sup">{t}Область/край{/t}</label>
                                        {$regcount=$order->regionList()}
                                        <span {if count($regcount) == 0}style="display:none"{/if} id="region-select">
                                            {$order.__addr_region_id->formView()}
                                        </span>

                                        <span {if count($regcount) > 0}style="display:none"{/if} id="region-input">
                                            {$order.__addr_region->formView()}
                                        </span>
                                    </div>
                                    <div class="form-group">
                                        <label class="label-sup">{t}Город{/t}</label>
                                        {$order->getPropertyView('addr_city')}
                                    </div>
                                    <div class="form-group">
                                        <label class="label-sup">{t}Индекс{/t}</label>
                                        {$order.__addr_zipcode->formView()}
                                    </div>
                                    <div class="form-group">
                                        <label class="label-sup">{t}Адрес{/t}</label>
                                        {$order->getPropertyView('addr_address')}
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="label-sup">{t}Контактное лицо{/t}</label>
                                    {$order->getPropertyView('contact_person', ['placeholder' => "{t}Лицо, которое встретит доставку. Например: Иван Иванович Пуговкин{/t}"])}
                                </div>
                            </div>
                        </div>
                    {else}
                        <input type="hidden" name="only_pickup_points" value="1"/>
                    {/if}
                    
                    {if $conf_userfields->notEmpty()}
                        <div class="t-order_contact-information">
                            <div class="additional">
                                <h3 class="h3">{t}Дополнительные сведения{/t}</h3>
                                {foreach $conf_userfields->getStructure() as $fld}
                                    <div class="form-group">
                                        <label class="label-sup">{$fld.title}</label>
                                        {$conf_userfields->getForm($fld.alias)}
                                        {$errname=$conf_userfields->getErrorForm($fld.alias)}
                                        {$error=$order->getErrorsByForm($errname, ', ')}
                                        {if !empty($error)}
                                            <span class="formFieldError">{$error}</span>
                                        {/if}
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    {/if}
                    
                    {if $CONFIG.enable_agreement_personal_data}
                        <div class="t-order_contact-information">
                            {include file="%site%/policy/agreement_phrase.tpl" button_title="{t}Далее{/t}"}
                        </div>
                    {/if}
                    <div class="form__menu_buttons text-center next">
                        <button type="submit" class="link link-more">{t}Далее{/t}</button>
                    </div>
                </form>
            </div>

    </div>
</div>
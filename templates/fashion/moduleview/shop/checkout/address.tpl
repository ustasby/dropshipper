{addjs file="order.js"}

<form method="POST" class="checkoutBox formStyle {$order.user_type|default:"authorized"}" id="order-form" data-city-autocomplete-url="{$router->getUrl('shop-front-checkout', ['Act'=>'searchcity'])}">
    {if !$is_auth}
    <div class="userType">
        <input type="radio" id="type-user" name="user_type" value="person" {if $order.user_type=='person'}checked{/if}><label for="type-user">Частное лицо</label>
        <input type="radio" id="type-company" name="user_type" value="company" {if $order.user_type=='company'}checked{/if}><label for="type-company">Компания</label>
        <input type="radio" id="type-noregister" name="user_type" value="noregister" {if $order.user_type=='noregister'}checked{/if}><label for="type-noregister">Без регистрации</label>
        <input type="radio" id="type-account" name="user_type" value="user" {if $order.user_type=='user'}checked{/if}><label for="type-account">Я регистрировался ранее</label>
    </div>
    {/if}    
    
    {$errors=$order->getNonFormErrors()}
    {if $errors}
        <div class="pageError">
            {foreach $errors as $item}
                <p>{$item}</p>
            {/foreach}
        </div>
    {/if}    
    <div class="newAccount">    
    <h2>Контактные данные</h2>
    {if $is_auth}
        <table class="themeTable companyTable">    
        {if $user.is_company}
            <tr>
                <td>Наименование компании</td>
                <td>{$user.company}</td>
            </tr>        
            <tr>
                <td>ИНН</td>
                <td>{$user.company_inn}</td>
            </tr>
        {/if}
            <tr>
                <td>Имя</td>
                <td>{$user.name}</td>
            </tr>
            <tr>
                <td>Фамилия</td>
                <td>{$user.surname}</td>
            </tr>
            <tr>
                <td>Отчество</td>
                <td>{$user.midname}</td>
            </tr>
            <tr>
                <td>Телефон</td>
                <td>{$user.phone}</td>
            </tr>            
            <tr>
                <td>E-mail</td>
                <td>{$user.e_mail}</td>
            </tr>                        
        </table>                    
        <div class="formLine changeUser">
            <a href="{urlmake logout=true}" class="link">Сменить пользователя</a>
        </div>
    {else}
        <div class="userRegister">
            <div class="organization">
                <div class="formLine">
                    <label class="fieldName">Наименование компании</label>
                    {$order->getPropertyView('reg_company')}
                    <div class="help">Например: ООО Аудиторская фирма "Аудитор"</div>
                </div>
                <div class="formLine">
                    <label class="fieldName">ИНН</label>
                    {$order->getPropertyView('reg_company_inn')}
                    <div class="help">10 или 12 цифр</div>
                </div>
            </div>
            <div class="formLine">
                <label class="fieldName">Имя</label>
                {$order->getPropertyView('reg_name')}
                <div class="help">Имя покупателя, владельца аккаунта</div>
            </div>
            <div class="formLine">
                <label class="fieldName">Фамилия</label>
                {$order->getPropertyView('reg_surname')}
                <div class="help">Фамилия покупателя, владельца аккаунта</div>
            </div>
            <div class="formLine">
                <label class="fieldName">Отчество</label>
                {$order->getPropertyView('reg_midname')}
            </div>        
            <div class="formLine">
                <label class="fieldName">Телефон</label>
                {$order->getPropertyView('reg_phone')}
                <div class="help">В формате: +7(123)9876543</div>
            </div>
            <div class="formLine">
                <label class="fieldName">e-mail</label>
                {$order->getPropertyView('reg_e_mail')}
            </div>
            
            <div class="formLine">
                <label class="fieldName">Пароль</label>
                <input type="checkbox" name="reg_autologin" {if $order.reg_autologin}checked{/if} value="1" id="reg-autologin">
                <label for="reg-autologin">Получить автоматически на e-mail</label>
                <div class="help">Нужен для проверки статуса заказа, обращения в поддержку, входа в кабинет</div>                    
                <div id="manual-login" {if $order.reg_autologin}style="display:none"{/if}>
                    <div class="inline">
                        {$order.__reg_openpass->formView(['form'])}
                        <div class="help">Пароль</div>
                    </div>
                    <div class="inline">
                        {$order.__reg_pass2->formView()}
                        <div class="help">Повтор пароля</div>
                    </div>
                    
                    <div class="formFieldError">{$order->getErrorsByForm('reg_openpass', ', ')}</div>
                </div>                
            </div>
            
            {foreach $reg_userfields->getStructure() as $fld}
                <div class="formLine">
                <label class="fieldName">{$fld.title}</label>
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
        
        <div class="userWithoutRegister">
           <div class="formLine">
                <label class="fieldName">Ф.И.О.</label>
                {$order->getPropertyView('user_fio')}
                <div class="help">Фамилия, Имя и Отчество покупателя, владельца аккаунта</div>
           </div>
           <div class="formLine">
                <label class="fieldName">E-mail</label>
                {$order->getPropertyView('user_email')}
                <div class="help">E-mail покупателя, владельца аккаунта</div>
           </div>
           <div class="formLine">
                <label class="fieldName">Телефон</label>
                {$order->getPropertyView('user_phone')}
                <div class="help">В формате: +7(123)9876543</div>
           </div>  
        </div>
        
        {if $have_to_address_delivery}
            <div class="address">
                <h2>Адрес</h2>
                {if $have_pickup_points} {* Если есть пункты самовывоза *}
                    <div class="formPickUpTypeWrapper"> 
                        <input id="onlyPickUpPoints" type="radio" name="only_pickup_points" value="1" {if $order.only_pickup_points}checked{/if}/> <label for="onlyPickUpPoints">{t}Самовывоз{/t}</label><br/>  
                        <input id="onlyDelivery" type="radio" name="only_pickup_points" value="0" {if !$order.only_pickup_points}checked{/if}/> <label for="onlyDelivery">{t}Доставка по адресу{/t}</label>  
                    </div>
                {/if}
                <div id="formAddressSectionWrapper" class="formAddressSectionWrapper {if $order.only_pickup_points}hidden{/if}">
                    {if count($address_list)>0}
                        <div class="formLine lastAddress">
                            {foreach $address_list as $address}
                                <span class="row">
                                    <input type="radio" name="use_addr" value="{$address.id}" id="adr_{$address.id}" {if $order.use_addr == $address.id}checked{/if}><label for="adr_{$address.id}">{$address->getLineView()}</label>
                                    <a href="{$router->getUrl('shop-front-checkout', ['Act' =>'deleteAddress', 'id' => $address.id])}" class="deleteAddress"></a>
                                    <br>
                                </span>
                            {/foreach}
                            <input type="radio" name="use_addr" value="0" id="use_addr_new" {if $order.use_addr == 0}checked{/if}><label for="use_addr_new">Другой адрес</label><br>
                        </div>
                    {else}
                        <input type="hidden" name="use_addr" value="0">
                    {/if}
                    
                    <div class="newAddress{if $order.use_addr>0 && $address_list} hide{/if}">
                        <div class="formLine">
                            <label class="fieldName">Страна</label>
                            {assign var=region_tools_url value=$router->getUrl('shop-front-regiontools', ["Act" => 'listByParent'])}
                            {$order->getPropertyView('addr_country_id', ['data-region-url' => $region_tools_url])}
                        </div>
                        <div class="formLine">
                            <span class="inline">
                                <label class="fieldName">Область/край</label>
                                {assign var=regcount value=$order->regionList()}
                                <span {if count($regcount) == 0}style="display:none"{/if} id="region-select">
                                    {$order.__addr_region_id->formView()}
                                </span>
                                
                                <span {if count($regcount) > 0}style="display:none"{/if} id="region-input">
                                    {$order.__addr_region->formView()}
                                </span>
                            </span>
                            <span class="inline">
                                <label class="fieldName">Город</label>
                                {$order->getPropertyView('addr_city')}
                            </span>
                        </div>
                        <div class="formLine">
                            <span class="inline">
                                <label class="fieldName">Индекс</label>
                                {$order.__addr_zipcode->formView()}
                            </span>
                            <span class="inline">
                                <label class="fieldName">Адрес</label>
                                {$order->getPropertyView('addr_address')}
                            </span>
                        </div>
                    </div>
                    <div class="formLine">
                        <label class="fieldName">Контактное лицо</label>
                        {$order->getPropertyView('contact_person')}
                        <p class="help">Лицо, которое встретит доставку. Например: Иван Иванович Пуговкин</p>
                    </div>
                </div>
            </div>
        {else}
            <input type="hidden" name="only_pickup_points" value="1"/>
        {/if}
        
        {if !$is_auth}
            <div class="formLine captcha">
                <label class="fieldName">{$order->__code->getTypeObject()->getFieldTitle()}</label>
                {$order->getPropertyView('code')}
            </div>
        {/if}
        {if $conf_userfields->notEmpty()}
            <div class="additional">
                <h2>Дополнительные сведения</h2>
                {foreach $conf_userfields->getStructure() as $fld}
                    <div class="formLine">
                        <label class="fieldName">{$fld.title}</label>
                        {$conf_userfields->getForm($fld.alias)}
                        {assign var=errname value=$conf_userfields->getErrorForm($fld.alias)}
                        {assign var=error value=$order->getErrorsByForm($errname, ', ')}
                        {if !empty($error)}
                            <span class="formFieldError">{$error}</span>
                        {/if}
                    </div>
                {/foreach}
            </div>
        {/if}
        
        <div class="buttons">
            {if $CONFIG.enable_agreement_personal_data}
                {include file="%site%/policy/agreement_phrase.tpl" button_title="{t}Далее{/t}"}
            {/if}
            <input type="submit" class="button" value="Далее">
        </div>
    </div>
    <div class="hasAccount">
        <div class="workArea">
            <h2>Вход</h2>
            <input type="hidden" name="ologin" value="1" id="doAuth" {if $order.user_type!='user'}disabled{/if}>
            <div class="formLine">
                <label class="fieldName">E-mail</label>
                {$order->getPropertyView('login')}
            </div>
            <div class="formLine">
                <label class="fieldName">Пароль</label>
                {$order->getPropertyView('password')}
            </div>
            <div class="buttonLine">
                <input type="submit" value="Войти">
            </div>
        </div>
    </div>    
</form>
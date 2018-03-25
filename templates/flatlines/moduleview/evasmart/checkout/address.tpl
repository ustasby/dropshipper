{* Оформление заказа. Шаг - Адрес *}

<div class="page-registration-steps">
    <div class="t-registration-steps">

        <div class="form-style">

            <form method="POST" class="t-order rs-order-form {$order.user_type|default:"authorized"}" data-city-autocomplete-url="{$router->getUrl('shop-front-checkout', ['Act'=>'searchcity'])}">
                {if $errors=$order->getNonFormErrors()}
                    <div class="page-error">
                        {foreach $errors as $item}
                            <p>{$item}</p>
                        {/foreach}
                    </div>
                {/if}

                <div class="t-order_contact-information">
                    <h3 class="h3">{t}Адрес{/t}</h3>
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

            </form>
        </div>
    </div>
</div>

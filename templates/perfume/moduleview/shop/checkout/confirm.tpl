{assign var=catalog_config value=ConfigLoader::byModule('catalog')}
{if $order->hasError()}
<div class="pageError">
    {foreach $order->getErrors() as $item}
    <p>{$item}</p>
    {/foreach}
</div>
{/if}

<form method="POST" class="formStyle checkoutForm">
    <div class="workArea noTopPadd">
        <h3 class="confirm">{t}Подтверждение заказа{/t}</h3>
        <div class="coInfo">
            {$user=$order->getUser()}
            <h2>{t}Сведения о заказе{/t}</h2>
            <div class="border">
                <table>
                    <tr>
                        <td class="key">{t}Заказчик{/t}</td>
                        <td>{$user.surname} {$user.name}</td>
                    </tr>
                    <tr>
                        <td class="key">{t}Телефон{/t}</td>
                        <td>{$user.phone}</td>
                    </tr>
                    <tr class="preSep">
                        <td class="key">e-mail</td>
                        <td>{$user.e_mail}</td>
                    </tr>
                    {$fmanager=$order->getFieldsManager()}
                    {if $fmanager->notEmpty()}
                        {foreach $fmanager->getStructure() as $field}
                            <tr class="{if $field@first}postSep{/if} {if $field@last}preSep{/if}">
                                <td class="key">{$field.title}</td>
                                <td>{$fmanager->textView($field.alias)}</td>
                            </tr>
                        {/foreach}
                    {/if}
                    {$delivery=$order->getDelivery()}
                    {$address=$order->getAddress()}
                    {$pay=$order->getPayment()}
                    {if $order.delivery}
                        <tr class="postSep">
                            <td class="key">{t}Доставка{/t}</td>
                            <td><a href="{$router->getUrl(null, ['Act' => 'delivery'])}">{$delivery.title}</a></td>
                        </tr>
                    {/if}
                    {if $order.only_pickup_points && $order.warehouse} {* Если только самовывоз *}
                        <tr>
                            <td class="key">{t}Пункт самовывоза{/t}</td>
                            <td><a href="{$router->getUrl(null, ['Act' => 'address'])}">{$order->getWarehouse()->adress}</a></td>
                        </tr>
                    {elseif $order.use_addr}
                        <tr>
                            <td class="key">{t}Адрес{/t}</td>
                            <td><a href="{$router->getUrl(null, ['Act' => 'address'])}">{$address->getLineView()}</a></td>
                        </tr>
                    {/if}
                    {if $order.payment}
                        <tr>
                            <td class="key">{t}Оплата{/t}</td>
                            <td><a href="{$router->getUrl(null, ['Act' => 'payment'])}">{$pay.title}</a></td>
                        </tr>
                    {/if}
                </table>
            </div>
        </div>            
        
        {$products=$cart->getProductItems()}
        {$cartdata=$cart->getCartData()}        
        <div class="coItems">
            {hook name="shop-checkout-confirm:products" title="{t}Подтверждение заказа:товары{/t}"}
            <table class="themeTable noMobile">
                <thead>
                    <tr>
                        <td>{t}Това{/t}р</td>
                        <td>{t}Количество{/t}</td>
                        <td class="price">{t}Цена{/t}</td>
                    </tr>
                </thead>
                <tbody>
                    {foreach $products as $n=>$item}
                    {$barcode=$item.product->getBarCode($item.cartitem.offer)}
                    {$offer_title=$item.product->getOfferTitle($item.cartitem.offer)}
                    {$multioffer_titles=$item.cartitem->getMultiOfferTitles()}
                    <tr>
                        <td><a href="{$item.product->getUrl()}">{$item.cartitem.title}</a>
                            <div class="codeLine">
                                {if $barcode != ''}{t}Артикул{/t}: <span class="value">{$barcode}</span><br>{/if}
                                {if $multioffer_titles || ($offer_title && $item.product->isOffersUse())}
                                    <div class="multioffersWrap">
                                        {foreach $multioffer_titles as $multioffer}
                                            <p class="value">{$multioffer.title} - {$multioffer.value}</p>
                                        {/foreach}
                                        {if !$multioffer_titles}
                                            <p class="value">{$offer_title}</p>
                                        {/if}
                                    </div>
                                {/if}
                            </div>
                        </td>
                        <td>{$item.cartitem.amount} 
                            {if $catalog_config.use_offer_unit}
                                {$item.product.offers.items[$item.cartitem.offer]->getUnit()->stitle}
                            {else}
                                {$item.product->getUnit()->stitle}
                            {/if}
                            {if !empty($cartdata.items[$n].amount_error)}<div class="amountError">{$cartdata.items[$n].amount_error}</div>{/if}
                        </td>
                        <td class="price">
                            {$cartdata.items[$n].cost}
                            <div class="discount">
                                {if $cartdata.items[$n].discount>0}
                                    {t}скидка{/t} {$cartdata.items[$n].discount}
                                {/if}
                            </div>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
            <br>
            <table class="themeTable noMobile">
                <tbody>
                    {foreach $cart->getCouponItems() as $id=>$item}
                    <tr>
                        <td>{t code=$item.coupon.code}Купон на скидку %code{/t}</td>
                        <td></td>
                    </tr>
                    {/foreach}
                    {if $cartdata.total_discount>0}
                    <tr>
                        <td>{t}Скидка на заказ{/t}</td>
                        <td>{$cartdata.total_discount}</td>
                    </tr>
                    {/if}
                    {foreach $cartdata.taxes as $tax}
                    <tr {if !$tax.tax.included}class="bold"{/if}>
                        <td>{$tax.tax->getTitle()}</td>
                        <td>{$tax.cost}</td>
                    </tr>
                    
                    {/foreach}
                    {if $order.delivery}
                        <tr>
                            <td>{t}Доставка{/t}: {$delivery.title}</td>
                            <td class="price">{$cartdata.delivery.cost}</td>
                        </tr>
                    {/if}
                    {if $cartdata.payment_commission}
                        <tr>
                            <td>{if $cartdata.payment_commission.cost>0}{t}Комиссия{/t}{else}{t}Скидка{/t}{/if}{t payment_title=$order->getPayment()->title} при оплате через "%payment_title"{/t}:</td>
                            <td class="price">{$cartdata.payment_commission.cost}</td>
                        </tr>
                    {/if}
                </tbody>
            </table>
            <div class="summary">
                <span class="text">{t}Итого{/t}: </span>
                <span class="price">{$cartdata.total}</span>
            </div>
            {/hook}
            <br>
            <div class="commentWrap">
                <label class="commentLabel">{t}Комментарий к заказу{/t}</label>
                {$order.__comments->formView()}
            </div>
            {if $this_controller->getModuleConfig()->require_license_agree}
            <br>
            <input type="checkbox" name="iagree" value="1" id="iagree"> <label for="iagree">{t alias="Оформление заказа - условия продаж" lic=$router->getUrl('shop-front-licenseagreement')}Я согласен с <a href="%lic" class="licAgreement inDialog">условиями предоставления услуг</a>{/t}</label>
            <script type="text/javascript">
                $(function() {
                    $('.formSave').click(function() {
                        if (!$('#iagree').prop('checked')) {
                            alert('{t}Подтвердите согласие с условиями предоставления услуг{/t}');
                            return false;
                        }
                    });
                });
            </script>
            {/if}            
        </div>
    </div>
    
    <div class="buttonLine alignRight">
        <input type="submit" value="{t}Подтвердить заказ{/t}" class="formSave">
    </div>
</form>
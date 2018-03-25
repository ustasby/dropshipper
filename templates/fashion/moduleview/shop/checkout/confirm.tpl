{assign var=catalog_config value=ConfigLoader::byModule('catalog')}
{if $order->hasError()}
<div class="pageError">
    {foreach $order->getErrors() as $item}
    <p>{$item}</p>
    {/foreach}
</div>
{/if}

<form method="POST" class="formStyle checkoutBox">
        <h3>Подтверждение заказа</h3>                
        <div class="coInfo">
            <div class="grayblock">
                {$user=$order->getUser()}
                <h2>Сведения о заказе</h2>
                <table>
                        <tr>
                            <td class="key">Заказчик</td>
                            <td>{$user.surname} {$user.name}</td>
                        </tr>
                        <tr>
                            <td class="key">Телефон</td>
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
                                    <td><a href="{$router->getUrl(null, ['Act' => 'address'])}">{$fmanager->textView($field.alias)}</a></td>
                                </tr>
                            {/foreach}
                        {/if}
                        {$delivery=$order->getDelivery()}
                        {$address=$order->getAddress()}
                        {$pay=$order->getPayment()}
                        {if $order.delivery}
                            <tr class="postSep">
                                <td class="key">Доставка</td>
                                <td><a href="{$router->getUrl(null, ['Act' => 'delivery'])}">{$delivery.title}</a></td>
                            </tr>
                        {/if}
                        {if $order.only_pickup_points && $order.warehouse} {* Если только самовывоз *}
                            <tr>
                                <td class="key">Пункт самовывоза</td>
                                <td><a href="{$router->getUrl(null, ['Act' => 'address'])}">{$order->getWarehouse()->adress}</a></td>
                            </tr>
                        {elseif $order.use_addr}
                            <tr>
                                <td class="key">Адрес</td>
                                <td><a href="{$router->getUrl(null, ['Act' => 'address'])}">{$address->getLineView()}</a></td>
                            </tr>
                        {/if}
                        {if $order.payment}
                            <tr>
                                <td class="key">Оплата</td>
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
            <table class="themeTable">
                <thead>
                    <tr>
                        <td>Товар</td>
                        <td>Количество</td>
                        <td class="price">Цена</td>
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
                                {if $barcode != ''}Артикул:<span class="value">{$barcode}</span><br>{/if}
                                {if $multioffer_titles || ($offer_title && $item.product->isOffersUse())}
                                    <div class="multioffersWrap">
                                        {foreach $multioffer_titles as $multioffer}
                                            <p class="value">{$multioffer.title} - <strong>{$multioffer.value}</strong></p>
                                        {/foreach}
                                        {if !$multioffer_titles}
                                            <p class="value"><strong>{$offer_title}</strong></p>
                                        {/if}
                                    </div>
                                {/if}
                            </div>
                        </td>
                        <td>
                         {$item.cartitem.amount}
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
                                скидка {$cartdata.items[$n].discount}
                                {/if}
                            </div>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
            <br>
            <table class="themeTable">
                <tbody>
                    {foreach $cart->getCouponItems() as $id=>$item}
                    <tr>
                        <td>Купон на скидку {$item.coupon.code}</td>
                        <td></td>
                    </tr>
                    {/foreach}
                    {if $cartdata.total_discount>0}
                    <tr>
                        <td>Скидка на заказ</td>
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
                            <td>Доставка: {$delivery.title}</td>
                            <td class="price">{$cartdata.delivery.cost}</td>
                        </tr>
                    {/if}
                    {if $cartdata.payment_commission}
                        <tr>
                            <td>Комиссия при оплате через "{$order->getPayment()->title}":</td>
                            <td class="price">{$cartdata.payment_commission.cost}</td>
                        </tr>
                    {/if}
                </tbody>
            </table>
            <div class="summary">
                <span class="text">Итого: </span> 
                <span class="price">{$cartdata.total}</span>
            </div>
            {/hook}
            <div class="commentSection">
                <label class="commentLabel">Комментарий к заказу</label>
                {$order.__comments->formView()}
            </div>
            {if $this_controller->getModuleConfig()->require_license_agree}
            <br>
            <input type="checkbox" name="iagree" value="1" id="iagree"> <label for="iagree">{t}Я согласен с <a href="{$router->getUrl('shop-front-licenseagreement')}" class="licAgreement inDialog">условиями предоставления услуг</a>{/t}</label>
            <script type="text/javascript">
                $(function() {
                    $('.formSave').click(function() {
                        if (!$('#iagree').prop('checked')) {
                            alert('Подтвердите согласие с условиями предоставления услуг');
                            return false;
                        }
                    });
                });
            </script>
            {/if}             
        </div>
        <div class="clearBoth"></div>
        <div class="buttons">
            <input type="submit" value="Подтвердить заказ" class="formSave">
        </div>
</form>
<form method="POST" action="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="authorization formStyle reserveForm">
        <input type="hidden" name="product_id" value="{$product.id}">
        <input type="hidden" name="offer_id" value="{$reserve.offer_id}">
        <input type="hidden" name="currency" value="{$product->getCurrencyCode()}">
        <h1 data-dialog-options='{ "width": "400" }'>Заказать</h1>        
        <div class="infotext">
            <p class="title">{$product.title} {$product.barcode}</p>    
            В данный момент товара нет в наличии. Заполните форму и мы оповестим вас о поступлении товара.
        </div>            
        <div class="forms">
            {if $reserve->hasError()}<div class="error">{implode(', ', $reserve->getErrors())}</div>{/if}
            <div class="center">
                <div class="formLine">
                    <label class="fieldName">Количество <br>
                    <input type="text" name="amount" class="amount" value="{$reserve.amount}">
                    <span class="incdec">
                        <a class="inc"></a>
                        <a class="dec"></a>
                    </span>
                    </label>
                </div>
                {if $product->isMultiOffersUse()}
                    <div class="formLine">
                        <strong>{$product.offer_caption|default:'Комплектация'}</strong>
                    </div>
                    {assign var=offers_levels value=$product.multioffers.levels} 
                    {foreach $offers_levels as $level}
                        <div class="formLine">
                            <label class="fielName">{$level.title|default:$level.prop_title}
                                <input name="multioffers[{$level.prop_id}]" value="{$reserve.multioffers[$level.prop_id]}" readonly>
                            </label>
                        </div>
                    {/foreach}
                {elseif $product->isOffersUse()}
                    {assign var=offers value=$product.offers.items}
                    <div class="formLine">
                        <label class="fielName">{$product.offer_caption|default:'Комплектация'}
                            <input name="offer" value="{$reserve.offer}" readonly>
                        </label>
                    </div>
                {/if} 
                <div class="formLine">
                    <label class="fieldName">Телефон
                        <input type="text" name="phone" class="inp" value="{$reserve.phone}">
                    </label>
                </div>
                <div class="formLine">
                    <label class="fieldName"><small>или</small> E-mail
                        <input type="text" name="email" class="inp" value="{$reserve.email}">
                    </label>
                </div>

                {if !$is_auth}
                <div class="formLine">
                    <label class="fieldName">
                        {$reserve->__kaptcha->getTypeObject()->getFieldTitle()}
                        {$reserve->getPropertyView('kaptcha')}
                    </label>
                </div>
                {/if}
            </div>
            <div class="buttons">
                <input type="submit" value="Оповестить меня">
            </div>
        </div>
</form>

<script>
    $(function() {
        $('.reserveForm .inc').off('click').on('click', function() {
            var amountField = $(this).closest('.reserveForm').find('.amount');
            amountField.val( (+amountField.val()|0)+1 );
        });
        
        $('.reserveForm .dec').off('click').on('click', function() {
            var amountField = $(this).closest('.reserveForm').find('.amount');
            var val = (+amountField.val()|0);
            if (val>1) {
                amountField.val( val-1 );
            }
        });
    });
</script>
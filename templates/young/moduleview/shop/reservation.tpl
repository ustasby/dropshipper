<form method="POST" action="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="authorization formStyle reserveForm">
        <input type="hidden" name="product_id" value="{$product.id}">
        <input type="hidden" name="offer_id" value="{$reserve.offer_id}">
        <input type="hidden" name="currency" value="{$product->getCurrencyCode()}">
        <div class="forms">
            <h2 data-dialog-options='{ "width": "400" }'>{t}Заказать{/t}</h2>
            <p class="title">{$product.title} {$product.barcode}</p>
            <p class="infotext">
                {t}В данный момент товара нет в наличии. Заполните форму и мы оповестим вас о поступлении товара.{/t}
            </p>
            {if $reserve->hasError()}<div class="error">{implode(', ', $reserve->getErrors())}</div>{/if}
            <div class="center">
                <div class="formLine">
                    <label class="fielName">{t}Количество{/t}</label><br>
                    <input type="number" min="{$product->getAmountStep()}" step="{$product->getAmountStep()}" name="amount" class="amount" value="{$reserve.amount}">
                    <div class="incdec">
                        <a class="inc" data-amount-step="{$product->getAmountStep()}"></a>
                        <a class="dec" data-amount-step="{$product->getAmountStep()}"></a>
                    </div>
                </div>
                {if $product->isMultiOffersUse()}
                    <div class="formLine">
                        <strong>{$product.offer_caption|default:t('Комплектация')}</strong>
                    </div>
                    {assign var=offers_levels value=$product.multioffers.levels} 
                    {foreach $offers_levels as $level}
                        <div class="formLine">
                            <label class="fielName">{$level.title|default:$level.prop_title}</label><br>
                            <input name="multioffers[{$level.prop_id}]" value="{$reserve.multioffers[$level.prop_id]}" readonly>
                        </div>
                    {/foreach}
                {elseif $product->isOffersUse()}
                    {assign var=offers value=$product.offers.items}
                    <div class="formLine">
                        <label class="fielName">{$product.offer_caption|default:t('Комплектация')}</label><br>
                        <input name="offer" value="{$reserve.offer}" readonly>
                    </div>
                {/if}
                <div class="formLine">
                    <label class="fielName">{t}Телефон{/t}</label><br>
                    <input type="text" name="phone" class="inp" value="{$reserve.phone}">
                </div>
                <div class="formLine">
                    <label class="fielName"><small>{t}или{/t}</small> {t}E-mail{/t}</label><br>
                    <input type="text" name="email" class="inp" value="{$reserve.email}">
                </div>
                {if !$is_auth}
                <div class="formLine">
                    <label class="fielName">{$reserve->__kaptcha->getTypeObject()->getFieldTitle()}</label><br>
                    {$reserve->getPropertyView('kaptcha')}
                </div>
                {/if}
            </div>
        </div>
        <input type="submit" value="{t}Оповестить меня{/t}">
        <br><br><br>
</form>

<script>
    $(function() {
        $('.reserveForm .inc').off('click').on('click', function() {
            var amountField = $(this).closest('.reserveForm').find('.amount');
            amountField.val( (+amountField.val()|0) + ($(this).data('amount-step')-0) );
        });
        
        $('.reserveForm .dec').off('click').on('click', function() {
            var amountField = $(this).closest('.reserveForm').find('.amount');
            var val = (+amountField.val()|0);
            if (val > $(this).data('amount-step')) {
                amountField.val( val - $(this).data('amount-step') );
            }
        });
    });
</script>
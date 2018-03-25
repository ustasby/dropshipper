<h2>Выберите способ оплаты</h2>
{if $api->hasError()}
    <div class="pageError pbottom">
        {foreach $api->getErrors() as $item}
        <p>{$item}</p>
        {/foreach}
    </div>
{/if} 
<div class="addMoney">
    <form method="POST" class="formStyle">
        <table class="formStyle">
            {foreach $pay_list as $item}
            <tr>
                <td class="radio">
                    <input type="radio" id="dlv_{$item.id}" value="{$item.id}" name="payment" {if $smarty.post.payment==$item.id}checked{/if}>
                </td>
                <td class="info">
                    <label for="dlv_{$item.id}">{$item.title}</label>
                    <div class="help">{$item.description}</div>
                </td>
            </tr>
            {/foreach}
        </table>
        
        <div class="addMoneyForm">
            <span class="text">Укажите сумму пополнения</span>
            <span class="summa"><input type="text" name="cost" value="{$smarty.post.cost}" placeholder="0.00"></span>
            <input type="submit" value="Пополнить">
        </div>
    </form>
</div>
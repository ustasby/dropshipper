<div class="confirmPay">
    <h2>Подтверждение оплаты</h2>

    {if $api->hasError()}
        <div class="pageError">
            {foreach $api->getErrors() as $item}
            <p>{$item}</p>
            {/foreach}
        </div>
    {/if}

    <table class="confirmPayTable">
        <tr>
            <td>Сумма</td>
            <td><strong>{$transaction->getCost(true, true)}</strong></td>
        </tr>
        <tr>
            <td>Назначение платежа</td>
            <td>{$transaction->reason}</td>
        </tr>    
        <tr>
            <td>Источник</td>
            <td>Лицевой счет</td>
        </tr>        
    </table>

    {if !$api->hasError()}
    <form method="POST" class="formStyle buttonLine">
        <input type="submit" value="Оплатить">
    </form>
    {/if}
</div>
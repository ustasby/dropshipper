<div class="confirmPay">
    <h2>{t}Подтверждение оплаты{/t}</h2>

    {if $api->hasError()}
        <div class="pageError">
            {foreach $api->getErrors() as $item}
            <p>{$item}</p>
            {/foreach}
        </div>
    {/if}

    <table class="confirmPayTable">
        <tr>
            <td>{t}Сумма{/t}</td>
            <td><strong>{$transaction->getCost(true, true)}</strong></td>
        </tr>
        <tr>
            <td>{t}Назначение платежа{/t}</td>
            <td>{$transaction->reason}</td>
        </tr>    
        <tr>
            <td>{t}Источник{/t}</td>
            <td>{t}Лицевой счет{/t}</td>
        </tr>        
    </table>

    {if !$api->hasError()}
    <form method="POST" class="formStyle buttonLine">
        <input type="submit" value="{t}Оплатить{/t}">
    </form>
    {/if}
</div>
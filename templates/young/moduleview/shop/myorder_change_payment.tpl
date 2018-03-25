<div class="authorization changePaymentWrapper formStyle">
    <h2 class="dialogTitle" data-dialog-options='{ "width": "400" }'>{t}Способы оплаты{/t}</h2>
    {if $success}
        <div class="infotext forms success">
            {t}Способ доставки изменен{/t}
        </div>
    {else}
        <form method="POST" enctype="multipart/form-data" action="{urlmake}">
            <div class="forms">
                {csrf}
                {$this_controller->myBlockIdInput()}

                {if $errors}
                    {foreach from=$errors item=error_field}
                        {foreach from=$error_field item=error}
                            <div class="error">{$error}</div>
                        {/foreach}
                    {/foreach}
                {/if}

                {foreach $payments as $payment}
                    <div class="formLine">
                        <label for="payment{$payment.id}" class="fielName">
                            <input id="payment{$payment.id}" {if $payment.id == $order.payment}checked{/if} type="radio" name="payment" value="{$payment.id}">
                            {$payment.title}</label>
                    </div>
                {/foreach}
                <input type="submit" value="{t}Отправить{/t}"/>
                <br><br><br>
            </div>
        </form>
    {/if}
</div>
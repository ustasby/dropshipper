<div class="changePaymentWrapper authorization  formStyle">
    <h2 class="dialogTitle" data-dialog-options='{ "width": "400" }'>{t}Способы оплаты{/t}</h2>
    {if $success}
        <div class="infotext forms success">
            {t}Способ доставки изменен{/t}
        </div>
    {else}
        <form method="POST" class="forms" enctype="multipart/form-data" action="{urlmake}">
            <div class="center">
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
                        <input id="payment{$payment.id}" {if $payment.id == $order.payment}checked{/if} type="radio" name="payment" value="{$payment.id}">
                        <label for="payment{$payment.id}" class="fielName">{$payment.title}</label>
                    </div>
                {/foreach}
                <div class="buttons">
                    <input type="submit" value="{t}Отправить{/t}"/>
                </div>
            </div>
        </form>
    {/if}
</div>
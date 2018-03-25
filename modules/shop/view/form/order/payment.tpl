<h3>{t}Оплата{/t} {if $elem.payment>0}<a href="{adminUrl do=paymentDialog order_id=$elem.id}" class="crud-add m-l-10" title="{t}редактировать{/t}"><i class="zmdi zmdi-edit"></i></a>{/if}</h3>

{if $elem.payment>0}
    {if isset($payment_id)}
       <input type="hidden" name="payment" value="{$payment_id}"/>
    {/if}
    <table class="otable">
        <tr>
            <td class="otitle">
                {t}Тип{/t}
            </td>
            <td>{$pay.title}</td>
        </tr>
        {if $elem.id>0}
            <tr>
                <td class="otitle">
                    {t}Заказ оплачен?{/t}
                </td>
                <td>
                    <div class="toggle-switch">
                        <input id="is_payed" name="is_payed" type="checkbox" hidden="hidden" {if $elem.is_payed}checked{/if} value="1">
                        <label for="is_payed" class="ts-helper"></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="otitle">
                    {t}Документы покупателя{/t}
                </td>
                <td>
                    {$type_object=$pay->getTypeObject()}
                    {foreach $type_object->getDocsName() as $key => $doc}
                        <a href="{$type_object->getDocUrl($key)}" class="underline" target="_blank">{$doc.title}</a>{if !$doc@last},{/if}
                    {foreachelse}
                        {t}Не предусмотрены{/t}
                    {/foreach}
                </td>
            </tr>
            {$transactions=$elem->getSuccessTransactions()}

            {if !empty($transactions)}
                <tr>
                    <td class="otitle">
                        {t}Успешная транзакция{/t}
                    </td>
                    <td>
                        {$last_transaction=current($transactions)}
                        <a href="{$router->getAdminUrl(false, ['f' => ['id' => $last_transaction['id']]], 'shop-transactionctrl', true)}">{t}Посмотреть{/t}</a>
                    </td>
                </tr>
            {/if}
        {/if}

        {$order_payment_fields}
    </table>

    {if $type_object}
        {$type_object->getAdminHTML($elem, ['order_consistent' => $order_consistent])}
    {/if}
{else}
    <p class="emptyOrderBlock">{t}Тип оплаты не указан.{/t} <a href="{adminUrl do=paymentDialog order_id=$elem.id}" class="u-link crud-add">{t}Указать оплату{/t}</a>.</p>
{/if}
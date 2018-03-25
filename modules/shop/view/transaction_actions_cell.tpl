{if !$cell->getRow()->checkSign()}
    <b style="color:red">{t}Неверная подпись{/t}</b>
{else}
    {if $cell->getRow()->personal_account && $cell->getRow()->status == 'new' && $cell->getRow()->order_id == 0}
        <a data-confirm-text="{t}Вы действительно желаете начислить средства по данной операции?{/t}" class="crud-get uline" href="{$router->getAdminUrl('setTransactionSuccess', ['id' => $cell->getRow()->id])}">{t}начислить средства{/t}</a>
    {/if}
    {if $cell->getRow()->status == 'success'
        && $cell->getRow()->cost > 0
        && ($cell->getRow()->receipt == 'no_receipt' || $cell->getRow()->receipt == 'fail')}
        <a data-confirm-text="{t}Вы действительно желаете выбить чек по данной операции?{/t}" class="crud-get uline" href="{$router->getAdminUrl('sendReceipt', ['id' => $cell->getRow()->id])}">{t}выбить чек{/t}</a>
    {/if}
    
    {if $cell->getRow()->status == 'success' && $cell->getRow()->receipt == 'receipt_success'}
        <a data-confirm-text="{t}Вы действительно желаете выбить чек возврата по данной операции?{/t}" class="crud-get uline" href="{$router->getAdminUrl('sendRefundReceipt', ['id' => $cell->getRow()->id])}">{t}сделать чек возврата{/t}</a>
    {/if}
{/if}
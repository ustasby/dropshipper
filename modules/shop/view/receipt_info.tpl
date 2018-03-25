{if !empty($info)}
    <div class="titlebox">{t}Информация о чеке{/t}</div>
    <table class="otable" border="0" cellpadding="5">
        {$m=0}
        {foreach $info as $key=>$val}
            {$m=$m+1}
            <tr {if !(($m%2) == 0)}class="hr"{/if}>
               <td><b>{$provider->getReceiptInfoStringByKey($key)}</b></td>
               <td>{$val}</td>
            </tr>
        {/foreach}
        {if !empty($receipt_url)}
            <tr>
                <td><b>{t}Ссылка на чек в ОФД{/t}</b></td>
                <td><a class="btn btn-default" href="{$receipt_url}" target="_blank">{t}Посмотреть{/t}</a></td>
                
            </tr>
        {/if}
    </table>
{else}
    {t}Информация не была записана.{/t}
{/if}
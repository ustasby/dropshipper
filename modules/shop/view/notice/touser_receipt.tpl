{$receipt_url=$data->receipt_url}
{$info=$data->info}
{$provider=$data->provider}
<p>{t}Спасибо за покупку!{/t}</p>

<p>{t}Информация о чеке{/t}</p>
<table class="otable" border="0" cellpadding="5">
    {$m=0}
    {foreach $info as $key=>$val}
        {$m=$m+1}
        <tr {if !(($m%2) == 0)}class="hr"{/if}>
           <td><b>{$provider->getReceiptInfoStringByKey($key)}</b></td>
           <td>{$val}</td>
        </tr>
    {/foreach}
</table>

<p>{t}Электронный чек доступен для проверки на адресу <a href="{$receipt_url}" target="_blank">{$receipt_url}</a>{/t}</p>

<p>{t}Администрация сайта{/t} {$url->getDomainStr()}.</p>
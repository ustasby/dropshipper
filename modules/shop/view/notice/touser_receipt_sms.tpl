{$receipt_url=$data->receipt_url}
{$info=$data->info}
{$provider=$data->provider}
{t}Чек:{/t}
{foreach $info as $key=>$val}
{$provider->getReceiptInfoStringByKey($key)}:{$val};
{/foreach}
{t}Ссылка на проверку чека:{/t} {$receipt_url}
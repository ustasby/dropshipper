{strip}
{if count($data->products_data)==1}
    {$first_product=current($data->products_data)}
    {t d=$url->getDomainStr()}На сайте %d заказан товар с артикулом №{/t}{$first_product.barcode}. {if $data->oneclick.user_fio}{$data->oneclick.user_fio}.{/if} {t}Телефон:{/t} {$data->oneclick.user_phone}
{else}
    {t d=$url->getDomainStr()}На сайте %d заказаны товары.{/t} {if $data->oneclick.user_fio}Заказчик {$data->oneclick.user_fio}.{/if} {t}Телефон:{/t} {$data->oneclick.user_phone}
{/if}
{/strip}
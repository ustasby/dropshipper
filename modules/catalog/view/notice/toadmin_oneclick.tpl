<p>{t d=$url->getDomainStr()}Уважаемый, администратор! На сайте %d хотят купить в 1 клик товары.{/t}</p>

<h3>{t}Контакты заказчика{/t}</h3>
<p>{t}Имя заказчика{/t}: {$data->oneclick.user_fio}</p>
<p>{t}Телефон{/t}: {$data->oneclick.user_phone}</p>

<h3>{t}Заказаны товары{/t}:</h3>

<table cellpadding="5" border="1" bordercolor="#969696" style="border-collapse:collapse; border:1px solid #969696">
    <thead>
        <tr>
            <th>ID</th>
            <th>{t}Наименование{/t}</th>
            <th>{t}Комплектация{/t}</th>
            <th>{t}Код{/t}</th>
            <th>{t}Кол-во{/t}</th>
        </tr>
    </thead>
    <tbody>
    {foreach $data->products_data as $product}
        {$offers_info=$product.offer_fields}
        <tr>
            <td><a href="{$router->getAdminUrl('edit',["id" => $product.id], 'catalog-ctrl', true)}">{$product.id}</a></td>
            <td><a href="{$router->getAdminUrl('edit',["id" => $product.id], 'catalog-ctrl', true)}">{$product.title}</a></td>
            <td>
                {if !empty($offers_info.offer)}
                    <h3>{t}Сведения о комплектации:{/t}</h3>
                    <a href="{$router->getAdminUrl('edit',["id" => $product.id], 'catalog-ctrl', true)}">{$offers_info.offer}</a>
                {elseif !empty($offers_info.multioffer)}
                    <h3>{t}Сведения о многомерное комплектации{/t}</h3>
                    {foreach $offers_info.multioffer as $offer}
                        {$offer}<br/>
                    {/foreach}
                {/if}
            </td>
            <td>{$product.barcode}</td>
            <td>{$offers_info.amount|default:1}</td>
        </tr>
    {/foreach}
    </tbody>
</table>



{if $data->ext_fields}
    <h3>{t}Дополнительные сведения{/t}</h3>
    <table cellpadding="5" border="1" bordercolor="#969696" style="border-collapse:collapse; border:1px solid #969696">
       <tbody>
          {foreach from=$data->ext_fields item=field}  
              <tr>
                 <td><b>{$field.title}</b></td>
                 <td>{$field.current_val}</td>
              </tr>
          {/foreach}
       </tbody>
    </table>
{/if}


<p>{t}Автоматическая рассылка{/t} {$url->getDomainStr()}.</p>
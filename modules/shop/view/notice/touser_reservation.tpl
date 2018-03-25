<p>{t}Уважаемый, клиент! Вы делали заказ на сайте{/t} <a href="http://{$url->getDomainStr()}">{$url->getDomainStr()}</a>, {t}уведомляем, что товар поступил на склад{/t}.</p>

<p>{t order_id=$data->reserve.id}Номер заказа: <strong>%order_id</strong> от{/t} <strong>{$data->reserve.dateof|date_format:"%d.%m.%Y %H:%M:%S"}</strong></p>

{assign var=product value=$data->reserve->getProduct()}


<h3>{t}Заказанный Вами товар{/t}</h3>
<table cellpadding="5" border="1" bordercolor="#969696" style="border-collapse:collapse; border:1px solid #969696">
    <thead>
        <tr>
            <th>ID</th>
            <th>{t}Наименование{/t}</th>
            {if $data->reserve.offer}
               <th>{t}Комплектации{/t}</th> 
            {elseif !empty($data->reserve.multioffer)}
               <th>{t}Комплектации{/t}</th> 
            {/if}
            <th>{t}Код{/t}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{$product.id}</td>
            <td>{$product.title}</td>
            {if $data->reserve.offer}
                <td>{$data->reserve.offer}</td>
            {elseif !empty($data->reserve.multioffer)}
                {assign var=multioffers value=unserialize($data->reserve.multioffer)}
                <td>
                {foreach $multioffers as $offer}
                    {$offer}<br/>
                {/foreach}
                </td>
            {/if}
            <td>{$product.barcode}</td>
        </tr>
    </tbody>
</table>

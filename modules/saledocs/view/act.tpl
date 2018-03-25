{$cart=$order->getCart()} {* объект корзины заказа *}
{$order_data=$cart->getOrderData(true, false)} {* состав заказа *}
{$user=$order->getUser()} {* покупатель *}
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
         body     { font-size:12px; font-family:"Arial"; }
         h1       { font-size:16px; } 
        .products { border:1px solid #000; border-collapse:collapse; width:100%; }
        .products td { border:1px solid #000; }
        
        #footer {
          position: fixed;
          bottom: 0px;
          right: 0px;
          height: 20px;
          text-align: right;
        }
        .pagenum:before {
          content: counter(page);
        }        
    </style>
</head>
<body>

<div id="footer"><p><span class="pagenum"></span></p></div>
<center>
    <h1>АКТ<br>
    приемки товаров и услуг <br>
    по заказу №{$order.order_num} от {$order.dateof|dateformat:"@date"}</h1>
</center>

<p>
{$CONFIG.firm_name}{if $CONFIG.firm_inn}, ИНН: {$CONFIG.firm_inn}{/if}, в {if $CONFIG.firm_v_lice}лице {$CONFIG.firm_v_lice}{else}собственном лице{/if}, 
{if $CONFIG.firm_deistvuet}действующий на основании {$CONFIG.firm_deistvuet},{/if} именуемый в дальнейшем Продавец, с одной стороны и 

{if $user.is_company}
    {$user.company}, в {if $user.company_v_lice}лице {$user.company_v_lice}{else}собственном лице{/if}, 
    {if $user.company_deistvuet}действующий на основании {$user.company_deistvuet},{/if}
{else}
    {$user->getFio()}, в собственном лице, паспорт {$user.passport},
{/if} именуемый в дальнейшем Покупатель, с другой стороны (в дальнейшем вместе именуемые «Стороны» и по отдельности «Сторона»), 
составили настоящий Акт о нижеследующем:</p>

<p>1. Продавец передает или оказывает, а Покупатель принимает следующие товары и услуги соответственно:</p>

<table class="products">
    <thead>
        <tr>
            <td align="center">№<br>п/п</td>
            <td>Наименование</td>
            <td align="center">Кол-во</td>
            <td align="right">Цена</td>
        </tr>
    </thead>
    <tbody>
        {foreach $order_data.items as $n=>$item}
        <tr>
            <td align="center">{$item@iteration}</td>
            <td>{$item.cartitem.title}
                <small>
                    {$multioffers_values = unserialize($item.cartitem.multioffers)}
                    {if !empty($multioffers_values)}
                        <div class="parameters">
                            {$offer = array()}
                            {foreach $multioffers_values as $mo_value}
                                {$offer[] = "{$mo_value.title}: {$mo_value.value}"} 
                            {/foreach}
                            {implode(', &nbsp; ', $offer)}
                        </div>
                    {elseif !empty($item.cartitem.model)}
                        <br>Модель: {$item.cartitem.model}
                    {/if}
                    {if $item.cartitem.barcode}<br>Артикул: {$item.cartitem.barcode}{/if}    
                </small>
            </td>
            <td align="center">{$item.cartitem.amount}</td>
            <td align="right">{$item.total}</td>
        </tr>
        {/foreach}
        {foreach $order_data.other as $n=>$item}
            {if $item.total>0}
            <tr>
                <td colspan="3">{$item.cartitem.title}</td>
                <td align="right">{$item.total}</td>
            </tr>
            {/if}
        {/foreach}
        <tr>
            <td colspan="3"><b>Итого</b></td>
            <td align="right"><b>{$order_data.total_cost}</b></td>
        </tr>
    </tbody>
</table>
<br>
{$totalcost_str = $rs_tools->priceToString($order_data.total_cost_noformat)}
<p>Стоимость товаров и услуг, предоставленных в соответствии с условиями Договора составляет 
<b>{$totalcost_str}</b>, с учетом налогов.</p>

<p>2. Принятые Покупателем товары и услуги обладают качеством, соответствующим требованиям Договора. 
Товары и услуги предоставлены в установленные в Договоре сроки. Покупатель не имеет никаких претензий к принятому товару и оказанным услугам.</p>

    <table cellpadding="5">
    <tr>
        <td width="50%" valign="top" style="border-right:1px solid #000">
            <h2>Продавец</h2>
            {$CONFIG.firm_name}
            <br>
            {if $CONFIG.firm_ogrn}<br>ОГРН(ИП): {$CONFIG.firm_ogrn}{/if}
            {if $CONFIG.firm_inn}<br>ИНН: {$CONFIG.firm_inn}{/if}
            {if $CONFIG.firm_kpp}<br>КПП: {$CONFIG.firm_kpp}{/if}
            <br>          
            {if $CONFIG.firm_address}<br>Юридический адрес:  {$CONFIG.firm_address}{/if}
            {if $CONFIG.firm_post_address}<br>Фактический адрес:  {$CONFIG.firm_post_address}{/if}
            <br>
            <br>___________/{$CONFIG.firm_director}/
            <br>м.п.
            <br><br>
        </td>
        <td width="50%" valign="top">
            <h2>Покупатель</h2>

            {if $user.is_company}
                {$user.company}
                <br>
                {if $user.company_inn}<br>ИНН: {$user.company_inn}{/if}
                {if $user.company_kpp}<br>КПП: {$user.company_kpp}{/if}
                <br>
                {if $user.company_address}<br>Юридический адрес:  {$user.company_address}{/if}
                {if $user.company_post_address}<br>Фактический адрес:  {$user.company_post_address}{/if}
            {else}
                {$user->getFio()}
                <br>
                <br>Паспорт: {$user.passport}
            {/if} 
            <br>
            <br>______________{if $user.company_director_fio}/{$user.company_director_fio}/{/if}
            {if $user.is_company}<br>м.п.{/if}
        </td>
    </tr>
    </table>
</body>
</html>
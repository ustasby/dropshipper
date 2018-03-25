{$cart=$order->getCart()} {* объект корзины заказа *}
{$order_data=$cart->getOrderData()} {* состав заказа *}
{$user=$order->getUser()}
<html>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<body>
    <style>
    body { font-family:Arial; font-size:12px; }    
    .bottomInfo{
        margin-top:20px;
    }
    .topTable{
       border-collapse:collapse;
       border-top:1px solid black;
       border-left:1px solid black;
    }

    .topTable td{
       border-bottom:1px solid black;
       border-right:1px solid black; 
       font-size:12px;
    }
    h1{
        font-size:26px;
        text-align:center;
        padding-top:20px;
        padding-bottom:20px;
    }
    </style>
    
    <div>
        <b>{$SITE.full_title}</b>
    </div>
    <table class="topTable" style="width:100%" cellpadding="3" cellspacing="0">
        <tr>
            <td style="width:25%">ИНН {$CONFIG.firm_inn}</td>
            <td style="width:25%">КПП {$CONFIG.firm_kpp}</td>
            
            <td style="width:10%" rowspan="2">Счёт №</td>
            <td style="width:40%" rowspan="2">{$CONFIG.firm_rs}</td>
        </tr>
        <tr>
            <td colspan="2" style="width:50%">{$CONFIG.firm_name}</td>
        </tr>
        <tr>
            <td colspan="2" style="width:50%"><div>Банк получателя:</div><div>{$CONFIG.firm_bank}</div></td>
            <td style="width:10%">
                <div>Бик</div>
                <div style="white-space:nowrap;">Кор.счёт №</div>
            </td>
            <td style="width:40%">
                <div>{$CONFIG.firm_bik}</div>
                <div>{$CONFIG.firm_ks}</div>
            </td>
        </tr>
    </table>
    <h1>СЧЁТ № ИМ-{$order.order_num} от {$order.dateof|dateformat:"@date"}</h1>
    <div>
        Плательщик: 
        {if $user.is_company}
            {$user.company}
        {else}
           {$user.surname} {$user.name} {$user.midname}
        {/if}
        {if !empty($user.company_inn)}, ИНН {$user.company_inn}{/if}
        {if !empty($user.company_bank)}, {$user.company_bank}{/if}
        {if !empty($user.company_bank_bik)}, БИК {$user.company_bank_bik}{/if}                
        {if !empty($user.company_rs)}, р/c {$user.company_rs}{/if}
        {if !empty($user.company_ks)}, к/c {$user.company_ks}{/if}
        {if !empty($user.company_address)}, юр.адрес: {$user.company_address}{/if}
        {if !empty($user.company_post_address)}, почтовый адрес: {$user.company_post_address}{/if}
        {if !empty($user.phone)}Тел.: {$user.phone}.{/if}
    </div>

<table class="topTable" width="100%">
    <thead>
        <tr>
            <td align="center">№<br>п/п</td>
            <td>Наименование</td>
            <td align="center">Кол-во</td>
            <td align="center">Ед.</td>
            <td align="right">Цена</td>
            <td align="right">Сумма</td>
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
            <td align="center">{$item.cartitem.data.unit}</td>
            <td align="right">{$item.single_cost}</td>
            <td align="right">{$item.total}</td>
        </tr>
        {/foreach}
        {foreach $order_data.other as $n=>$item}
            {if $item.total>0}
            <tr>
                <td colspan="5">{$item.cartitem.title}</td>
                <td align="right">{$item.total}</td>
            </tr>
            {/if}
        {/foreach}
        <tr>
            <td colspan="5"><b>Итого</b></td>
            <td align="right"><b>{$order_data.total_cost}</b></td>
        </tr>
    </tbody>
</table>    
    <div class="bottomInfo">
        <p>
            {$totalcost_str = $rs_tools->priceToString($order_data.total_cost_noformat)}
            Всего наименований на сумму {$order_data.total_cost}
            ({$totalcost_str})
        </p>
        <p>
            Счёт действителен в течении 5 банковских дней.
        </p>
    </div>
</body>
</html>
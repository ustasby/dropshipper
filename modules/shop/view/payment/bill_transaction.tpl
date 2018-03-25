<!doctype html>
{assign var=pay value=$transaction->getPayment()->getTypeObject()}
{assign var=user value=$transaction->getUser()}
{assign var=company value=$transaction->getPayment()->getShopCompany()}
{*assign var=order_data value=$order->getCart()->getOrderData(true, false, false)*}
<html>
<head>
    <title>Счет на оплату заказа N {$pay->getOption('number_prefix')}{$order.order_num} от {$order.dateof|date_format:"%d.%m.%Y"}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { width: 210mm; margin-left: auto; margin-right: auto; border: 1px #efefef solid; font-size: 11pt;}
        table.invoice_bank_rekv { border-collapse: collapse; border: 1px solid; }
        table.invoice_bank_rekv > tbody > tr > td, table.invoice_bank_rekv > tr > td { border: 1px solid; }
        table.invoice_items { border: 1px solid; border-collapse: collapse;}
        table.invoice_items td, table.invoice_items th { border: 1px solid;}
    </style>
</head>
<body>
<table width="100%">
    <tr>
        <td>&nbsp;</td>

        <td style="width: 155mm;">
            <div style="width:155mm; "></div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <div style="text-align:center;  font-weight:bold; font-size:20px;">
                Образец заполнения платежного поручения
            </div>
        </td>
    </tr>
</table>


<table width="100%" cellpadding="2" cellspacing="2" class="invoice_bank_rekv">
    <tr>

        <td style="min-height:6mm; height:auto; width: 50mm;">
            <div>ИНН {$company.firm_inn}</div>
        </td>
        <td style="min-height:6mm; height:auto; width: 55mm;">
            <div>КПП {$company.firm_kpp}</div>
        </td>
        <td rowspan="2" style="min-height:19mm; height:auto; vertical-align: top; width: 25mm;">
            <div>Сч. №</div>

        </td>
        <td rowspan="2" style="min-height:19mm; height:auto; vertical-align: top; width: 60mm;">
            <div>{$company.firm_rs}</div>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="min-height:13mm; height:auto;">

            <table border="0" cellpadding="0" cellspacing="0" style="height: 13mm; width: 105mm;">

                <tr>
                    <td valign="top">
                        <div>{$company.firm_name}</div>
                    </td>
                </tr>
                <tr>
                    <td valign="bottom" style="height: 3mm;">
                        <div style="font-size: 10pt;">Получатель</div>

                    </td>
                </tr>
            </table>

        </td>
    </tr>
<tr>
        <td colspan="2" rowspan="2" style="min-height:13mm; width: 105mm;">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="height: 13mm;">
                <tr>
                    <td valign="top">

                        <div>{$company.firm_bank}</div>
                    </td>
                </tr>
                <tr>
                    <td valign="bottom" style="height: 3mm;">
                        <div style="font-size:10pt;">Банк получателя</div>
                    </td>
                </tr>

            </table>
        </td>
        <td style="min-height:7mm;height:auto; width: 25mm;">
            <div>БИK</div>
        </td>
        <td rowspan="2" style="vertical-align: top; width: 60mm;">
            <div style=" height: 7mm; line-height: 7mm; vertical-align: middle;">{$company.firm_bik}</div>
            <div>{$company.firm_ks}</div>

        </td>
    </tr>
    <tr>
        <td style="width: 25mm;">
            <div>Корр. сч. №</div>
        </td>
    </tr>    
    
</table>
<br/>

<div style="font-weight: bold; font-size: 16pt; padding-left:5px;">
    Счет № {$pay->getOption('number_prefix')}T{$transaction.id} от {$transaction.dateof|date_format:"%d.%m.%Y"}</div>

<br/>

<div style="background-color:#000000; width:100%; font-size:1px; height:2px;">&nbsp;</div>

<table width="100%">
    <tr>
        <td style="width: 30mm;">
            <div style=" padding-left:2px;">Покупатель:    </div>
        </td>
        <td>

            <div style="font-weight:bold;  padding-left:2px;">
                {if $user.is_company}
                    {$user.company}, ИНН: {$user.company_inn}, Тел.: {$user.phone}, ID: {$user.id}
                {else}
                    {$user.surname} {$user.name} {$user.midname}, Тел: {$user.phone}, ID: {$user.id}
                {/if}
            </div>
        </td>
    </tr>
</table>


<table class="invoice_items" width="100%" cellpadding="2" cellspacing="2">
    <thead>
    <tr>

        <th style="width:13mm;">№</th>
        <th>Товар</th>
        <th style="width:20mm;">Кол-во</th>
        <th style="width:17mm;">Ед.</th>
        <th style="width:27mm;">Цена</th>

        <th style="width:27mm;">Сумма</th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <td align="center">1</td>
            <td align="left">{$transaction->reason}</td>
            <td align="right">1</td>
            <td align="left">шт</td>
            <td align="right">{$transaction->cost}</td>
            <td align="right">{$transaction->cost}</td>
        </tr>
    </tbody>

</table>

<table border="0" width="100%" cellpadding="1" cellspacing="1">
    <tr>
        <td></td>
        <td style="width:27mm; font-weight:bold;  text-align:right;">Итого:</td>
        <td style="width:27mm; font-weight:bold;  text-align:right;">{$transaction->cost}</td>
    </tr>
</table>

<br />
<div>
Всего наименований 1 на сумму {$transaction->cost} рублей.<br />
<strong>{$sumstr}</strong></div>
<br /><br />
<div style="background-color:#000000; width:100%; font-size:1px; height:2px;">&nbsp;</div>
<br/>

<div>Руководитель ______________________ ({$company.firm_director})</div>
<br/>

{if !empty($company.firm_accountant)}
<div>Главный бухгалтер ______________________ ({$company.firm_accountant})</div>
<br/>
{/if}

<div style="width: 85mm;text-align:center;">М.П.</div>
<br/>


<div style="width:800px;text-align:left;font-size:10pt;">Счет действителен к оплате в течении трех дней.</div>

</body>
</html>
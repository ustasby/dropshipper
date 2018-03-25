{addcss file="flatadmin/debug.css" basepath="common"}
{$app->setBodyClass('module-debug-body')}

<h3 class="module-debug-title">{t}Переменные, которые были переданы в шаблон модулем{/t}</h3>

<table class="module-debug-info-vars">
<thead>
    <tr>
        <th>{t}Имя переменной{/t}</th>
        <th>{t}Тип{/t}</th>
    </tr>
</thead>
<tbody>
    {foreach from=$var_list item=item}
    <tr>
        <td class="var-name">{$item.key}</td>
        <td class="var-type">{$item.type}</td>
    </tr>
    {/foreach}
</tbody>
</table>
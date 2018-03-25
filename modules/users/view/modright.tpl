&nbsp;
<a href="JavaScript:;" onclick="$(this).nextAll('input:checkbox').attr('checked','checked')" style="text-decoration:underline" title="{t}Нажмите, чтобы включить все привелегии модуля{/t}">{t}Максимум{/t}</a>
{assign var=row value=$cell->getRow()}
{foreach from=$row.bits item=checked key=n}
   <input type="checkbox" name="module_access[{$row.class}][]" value="{$n}" title="{t}{$row.accessbit.$n|default:"Не используется"}{/t}" {if $checked}checked{/if}>
{/foreach}

<a href="JavaScript:;" onclick="$(this).prevAll('input:checkbox').removeAttr('checked')" style="text-decoration:underline" title="{t}Нажмите, чтобы отключить привилегии модуля{/t}">{t}Нет прав{/t}</a>
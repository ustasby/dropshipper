<select name="{$cell->getSelectName()}">
    {foreach from=$cell->getItems() key=key item=item}
    <option value="{$key}" {if $cell->getValue()==$key}selected{/if}>{$item}</option>
    {/foreach}
</select>
{if $field->isHaveAttrKey('listFilter')} {* Если нужен фильтр *}
    {addjs file="jquery.rs.selectfilter.js" basepath="common"}
    <div class="selectFilterWrapper">
        <div class="selectFilter">
            <input type="text" class="filter" placeholder="{t}Фильтр{/t}"/>
        </div>
{/if}
        <select name="{$field->getFormName()}" {$field->getAttr()}>
            {rshtml_options options=$field->getList() selected=$field->get()}
        </select>
{if $field->getAttrByKey('listFilter')}
    </div>
{/if}
{include file="%system%/coreobject/type/form/block_error.tpl"}
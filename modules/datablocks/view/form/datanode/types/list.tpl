{extends file="%datablocks%/form/datanode/types/default.tpl"}
{block name="values"}
    {include file="%system%/admin/keyvaleditor.tpl" field_name="child_structure[{$uniq_key}][list_values]" arr=$field.list_values}
{/block}
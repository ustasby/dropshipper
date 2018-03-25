<div class="{if $level.section.element_type == 'row'}row{else}{*
    *}{include file="%system%/gs/bootstrap/attribute.tpl" field="width"}{*
    *}{include file="%system%/gs/bootstrap/attribute.tpl" field="prefix" name="offset-"}{*
    *}{include file="%system%/gs/bootstrap/attribute.tpl" field="pull" name="pull-"}{*
    *}{include file="%system%/gs/bootstrap/attribute.tpl" field="push" name="push-"}{/if} {*
    *}{if $level.section.css_class}{$level.section.css_class}{/if}">
    
    {if !empty($level.childs)}
        {include file="%system%/gs/{$layouts.grid_system}/sections.tpl" item=$level.childs assign=wrapped_content}
    {else}
        {include file="%system%/gs/blocks.tpl" assign=wrapped_content}
    {/if}
    
    {if $level.section.inset_template}
        {include file=$level.section.inset_template wrapped_content=$wrapped_content}
    {else}
        {$wrapped_content}
    {/if}        
    
</div>
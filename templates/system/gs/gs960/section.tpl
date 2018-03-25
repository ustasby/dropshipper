<div class="grid_{$level.section.width}{if $level.section.prefix} prefix_{$level.section.prefix}{/if}{if $level.section.suffix} suffix_{$level.section.suffix}{/if}{if $level.section.pull} pull_{$level.section.pull}{/if}{if $level.section.push} push_{$level.section.push}{/if}{if $level.section.parent_id>0}{if $is_first} alpha{/if}{if $is_last} omega{/if}{/if} {if $level.section.css_class}{$level.section.css_class}{/if}">
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
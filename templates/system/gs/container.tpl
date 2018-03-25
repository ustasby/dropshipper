{* Общий шаблон одного контейнера *}
{strip}
{if $layouts.grid_system == 'gs960'}{$container_grid_class="container_{$container.columns}"}
{elseif $layouts.grid_system == 'bootstrap'}{$container_grid_class="container{if $container.is_fluid}-fluid{/if}"}{/if}
{if $container.wrap_element}<{$container.wrap_element} class="{$container.wrap_css_class}">{/if}
<div class="{$container_grid_class} {$container.css_class}">
    {if $layouts.sections[$container.id]}
        {include file="%system%/gs/{$layouts.grid_system}/sections.tpl" item=$layouts.sections[$container.id] assign=wrapped_content}
    {else}
        {assign var=wrapped_content value=""}    
    {/if}
    
    {if $container.inside_template}
        {include file=$container.inside_template wrapped_content=$wrapped_content}
    {else}
        {$wrapped_content}
    {/if}
</div>
{if $container.wrap_element}</{$container.wrap_element}>{/if}
{/strip}
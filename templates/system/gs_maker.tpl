{strip}
{foreach from=$layouts['containers'] item=container}
    {include file="%system%/gs/container.tpl" container=$container assign=wrapped_content}
    {if $container.outside_template}
        {include file=$container.outside_template wrapped_content=$wrapped_content}
    {else}
        {$wrapped_content}
    {/if}    
{/foreach}
{/strip}
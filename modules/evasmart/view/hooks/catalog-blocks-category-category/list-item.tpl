{foreach from=$dirlist item=dir}
    <li {if in_array($dir.fields.id, $pathids)}class="act"{/if} {$dir.fields->getDebugAttributes()}>
        <a href="{$dir.fields->getUrl()}">{$dir.fields.name}</a>
        {if !empty($dir.child)}
            {assign var=cnt value=count($dir.child)}
            {if $cnt>9 && $cnt<21}
                {assign var=columns value="twoColumn"}
            {elseif $cnt>20}
                {assign var=columns value="threeColumn"}
            {/if}

        {/if}
    </li>
{/foreach}
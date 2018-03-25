{if $CONFIG.facebook_group || $CONFIG.vkontakte_group || $CONFIG.twitter_group}
<div class="socialLine">
    {if $CONFIG.facebook_group}
    <a href="{$CONFIG.facebook_group}" class="fb"></a>
    {/if}
    {if $CONFIG.vkontakte_group}
    <a href="{$CONFIG.vkontakte_group}" class="vk"></a>
    {/if}
    {if $CONFIG.twitter_group}
    <a href="{$CONFIG.twitter_group}" class="tw"></a>
    {/if}
    {if $CONFIG.instagram_group}
    <a href="{$CONFIG.instagram_group}" class="instagram"></a>
    {/if}
</div>
{/if}
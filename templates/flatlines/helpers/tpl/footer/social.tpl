{* Ссылки на группы в социальных сетях *}
{if    $CONFIG.facebook_group
    || $CONFIG.vkontakte_group
    || $CONFIG.twitter_group
    || $CONFIG.instagram_group}
<div class="column">
    <div class="column_title">
        <span>{t}МЫ В СОЦСЕТЯХ{/t}</span>
        </div>
        <div class="footer-social_wrapper">
            <div class="block-social">
                {if $CONFIG.facebook_group}
                    <a href="{$CONFIG.facebook_group}" class="facebook"></a>
                {/if}
                {if $CONFIG.vkontakte_group}
                    <a href="{$CONFIG.vkontakte_group}" class="vk"></a>
                {/if}
                {if $CONFIG.twitter_group}
                    <a href="{$CONFIG.twitter_group}" class="twitter"></a>
                {/if}
                {if $CONFIG.instagram_group}
                    <a href="{$CONFIG.instagram_group}" class="instagram"></a>
                {/if}
            </div>
        </div>
        <!-- footer-social_wrapper-->
    </div>

{/if}
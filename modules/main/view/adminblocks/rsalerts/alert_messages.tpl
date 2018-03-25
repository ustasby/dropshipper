{if $messages}
    <div class="list-group rs-alerts-section">
        {foreach $messages as $message}
            <a class="list-group-item {$message.status}" {if $message.href}href="{$message.href}"{/if} {if $message.target}target="{$message.target}"{/if}>
                <span class="message">{$message.message}</span>
                {if $message.description}
                    <span class="description">{$message.description}</span>
                {/if}
            </a>
        {/foreach}
    </div>
{else}
    <div class="rs-side-panel__empty">
        {t}Нет уведомлений{/t}
    </div>
{/if}
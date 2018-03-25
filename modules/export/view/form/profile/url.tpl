{if $elem->id}
    <b><a href="{$elem->getExportUrl()}">{$elem->getExportUrl()}</a></b>
{else}
    {t}Адрес будет доступен после сохранения профиля{/t}
{/if}
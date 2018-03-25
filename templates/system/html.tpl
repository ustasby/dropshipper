<!DOCTYPE {$app->getDoctype()}>
<html {$app->getHtmlAttrLine()} {if $SITE.language}lang="{$SITE.language}"{/if}>
<head {$app->getHeadAttributes(true)}>
<title>{$app->title->get()}</title>
{$app->meta->get()}
{foreach from=$app->getCss() item=css}
{$css.params.before}<link {if $css.params.type !== false}type="{$css.params.type|default:"text/css"}"{/if} href="{$css.file}" {if $css.params.media!==false}media="{$css.params.media|default:"all"}"{/if} rel="{$css.params.rel|default:"stylesheet"}">{$css.params.after}
{/foreach}
<script>
    var global = {$app->getJsonJsVars()};
</script>
{foreach from=$app->getJs() item=js}
{$js.params.before}<script type="{$js.params.type|default:"text/javascript"}" src="{$js.file}"></script>{$js.params.after}
{/foreach}
{if $app->getJsCode()!=''}
<script language="JavaScript">{$app->getJsCode()}</script>
{/if}
{$app->getAnyHeadData()}
</head>
<body {if $app->getBodyClass()!= ''}class="{$app->getBodyClass()}"{/if} {$app->getBodyAttrLine()}>
    {$body}
    {* Нижние скрипты *}
    {foreach from=$app->getJs('footer') item=js}
    {$js.params.before}<script type="{$js.params.type|default:"text/javascript"}" src="{$js.file}" defer></script>{$js.params.after}
    {/foreach}    
</body>
</html>
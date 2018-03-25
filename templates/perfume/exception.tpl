<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>{t}Ой, ошибочка{/t} {$error.code}</title>
<!--[if lt IE 9]>
<script src="{$THEME_JS}/html5shiv.js"></script>
<![endif]-->
<link rel="stylesheet" type="text/css" href="{$THEME_CSS}/960gs/reset.css">
<link rel="stylesheet" type="text/css" href="{$THEME_CSS}/960gs/960.css">
<link rel="stylesheet" type="text/css" href="{$THEME_CSS}/style.css">
{if $THEME_SHADE !== 'pink'}
<link rel="stylesheet" type="text/css" href="{$THEME_CSS}/{$THEME_SHADE}.css">
{/if}

</head>
<body class="exceptionBody">
    <a class="exLogo" href="{$site->getRootUrl()}"><img src="{$site_config.__logo->getUrl(160, 65)}"></a>
    <div class="exError">
        <p class="before">{t}ошибка{/t}</p>
        <p class="code">{$error.code}</p>
        <p class="after">{$error.comment}</p>
    </div>
    <a href="{$site->getRootUrl()}" class="toRoot"><i></i>{t}На главную{/t}</a>
</html>
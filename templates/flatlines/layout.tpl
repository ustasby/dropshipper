{* Основной шаблон *}
{strip}
{addmeta http-equiv="X-UA-Compatible" content="IE=Edge" unshift=true}
{addmeta name="viewport" content="width=device-width, initial-scale=1.0"}

{if $THEME_SETTINGS.enable_page_fade}
    {$app->setBodyAttr(['style' => 'opacity:0', 'onload' => "setTimeout('document.body.style.opacity = &quot;1&quot;', 0)"])}
{/if}

{addcss file="/rss-news/" basepath="root" rel="alternate" type="application/rss+xml" title="t('Новости')"}
{addcss file="libs/bootstrap.min.css"}
{addcss file="libs/bootstrap-theme.min.css"}
{addcss file="libs/pe-icon-7-stroke.css"}
{addcss file="libs/helper.css"}
{addcss file="libs/magnific-popup.css"}
{addcss file="common/lightgallery/css/lightgallery.min.css" basepath="common"}

{addcss file="main.css"}
{if $THEME_SHADE !== 'blue'}
    {addcss file="{$THEME_SHADE}.css"}
{/if}

{addjs file="libs/jquery.min.js" name="jquery" unshift=true header=true}
{addjs file="libs/bootstrap.min.js" name="bootstrap"}
{addjs file="jquery.form/jquery.form.js" basepath="common"}
{addjs file="jquery.cookie/jquery.cookie.js" basepath="common"}
{addjs file="libs/jquery.sticky.js"}
{addjs file="libs/jquery.magnific-popup.min.js"}

{addjs file="lightgallery/lightgallery-all.min.js" basepath="common"}

{addjs file="rs.profile.js"}
{addjs file="rs.changeoffer.js"}
{addjs file="rs.indialog.js"}
{addjs file="rs.cart.js"}
{addjs file="rs.theme.js"}

{* Добавляем класс shopBase, если комплектация системы - Витрина *}
{if $shop_config === false}
    {$app->setBodyClass('shopBase', true)}
{else}
    {$app->setBodyClass('noShopBase', true)}
{/if}

{/strip}
{$app->blocks->renderLayout()}

{* Подключаем файл scripts.tpl, если он существует в папке темы. В данном файле 
рекомендуется добавлять JavaScript код, который должен присутствовать на всех страницах сайта *}
{tryinclude file="%THEME%/scripts.tpl"}
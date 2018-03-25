<div class="notice-box notice-bg">
    {t}Файл sitemap для текущего сайта находится по адресу:{/t}
    <strong>{$router->getUrl('sitemap-front-sitemap', [site_id => $SITE.id], true)}</strong><br>
    <br>
    {t}Файл sitemap с дополнительными элементами для google находится по адресу:{/t}
    <strong>{$router->getUrl('sitemap-front-sitemap', [site_id => $SITE.id, type => 'google'], true)}</strong>
</div>
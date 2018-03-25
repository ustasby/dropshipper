{* Выполняет POST запрос на продление лицензии *}
<form method="POST" action="{$Setup.RS_SERVER_PROTOCOL}://{$Setup.RS_SERVER_DOMAIN}/update-temp/">
    <input type="hidden" name="license" value="{$license}">
    <input type="hidden" name="domain" value="{$domain}">
    <input type="hidden" name="shop_url" value="{$shop_url}">

    <input type="submit" value="{t}Продолжить{/t}" id="sub">
</form>
<script type="text/javascript">
    document.getElementById('sub').style.display = 'none';
    document.forms[0].submit();
</script>
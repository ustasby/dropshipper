function delModule(formid)
{
    if( typeof( $(formid).formToArray ) == 'function' )
        data = $(formid).formToArray();
    else if( typeof( $(formid).serializeArray ) == 'function' )
        data = $(formid).serializeArray();    
    
    $.getJSON('?ajax=1&do=del', data, function(result) {
        window.location.reload();
        if (result.status != 'ok') {
            if (result.reason) alert(result.reason);
        }
    });
};

/**
* SingleTon Операции с модулем
*/
var modTools = {
    resultDiv: '#ajaxresult',
    exec: function(url, modname)
    {
        if (confirm(lang.t('Вы действительно хотите выполнить данное действие?'))) {
            $.getJSON(url, {modname: modname}, modTools.writeResult);
        }
    },
    
    writeResult: function(data)
    {
        if (!data) return false;
        var resultDiv = $(modTools.resultDiv);
        resultDiv.removeClass('ok fail hidden');
        resultDiv.addClass((data.status == 'ok') ? 'ok' : 'fail');
        resultDiv.html(data.result);
    }
};
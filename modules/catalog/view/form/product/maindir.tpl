<select name="maindir" id="maindir" data-selected="{$elem.maindir}">
    <option value="">-- {t}не выбрано{/t} --</option>
</select>

<script>
$("select[name='xdir[]']").change(onDirChange);

function onDirChange(e, firstRun )
{
    var xdir = $("select[name='xdir[]']");
    var maindir = $('#maindir');
    
    maindir.html('');
    var selected = $("option:selected", xdir);
    if (selected.length == 0) {
        maindir.append('<option value="">-- {t}не выбрано{/t} --</option>');
    }
    selected.each(function() {
        var cur = $(this);
        var n = cur.attr('class').split('_')[1];
        var fulloption = '';
        var delim = '';
    
        while(n !==null) {
            fulloption = cur.attr('data-value') + delim + fulloption;
            cur = cur.prevAll('[class=lev_'+(n-1)+']:first');
            var n = (cur.length>0) ? cur.attr('class').split('_')[1] : null;
            delim = ' > ';
        }
        maindir.append('<option value="'+$(this).attr('value')+'">' + fulloption + '</option>');
    });
    var main_selected = (firstRun) ?  maindir.attr('data-selected') : $('#maindir option:first').val();
    maindir.val(main_selected);
}

onDirChange(null, true );

</script>
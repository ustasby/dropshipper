<div class="formbox">
    <form method="POST" action="{urlmake}" enctype="multipart/form-data" class="crud-form"
          data-dialog-options='{ "width":500, "height":400 }'>
        <input type="submit" value="" style="display:none">
        <div class="notabs">
            <label>{t}Csv файл:{/t}</label>
            {include file="%system%/admin/fileinput.tpl" form_name="csvfile"}


        </div>
    </form>
</div>
{addjs file="%templates%/devicehl.js"}
<table class="bootstrap-multi-values">
    <tr>
        <td class="xs">{include file=$elem["__{$field}_xs"]->getOriginalTemplate() field=$elem["__{$field}_xs"]}</td>
        <td class="sm">{include file=$elem["__{$field}_sm"]->getOriginalTemplate() field=$elem["__{$field}_sm"]}</td>
        <td class="md">{include file=$elem["__{$field}"]->getOriginalTemplate() field=$elem["__{$field}"]}</td>
        <td class="lg">{include file=$elem["__{$field}_lg"]->getOriginalTemplate() field=$elem["__{$field}_lg"]}</td>
    </tr>
    <tr class="bootstrap-subtext">
        <td class="xs">XS</td>
        <td class="sm">SM</td>
        <td class="md">MD</td>
        <td class="lg">LG</td>
    </tr>
</table>
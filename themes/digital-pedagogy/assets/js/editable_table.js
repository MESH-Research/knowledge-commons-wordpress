const $edittableID = $('.editable-table');

function cloneRow (table) {
    let v = table.find('tbody tr').last().clone(true).removeClass('hide table-line hidden');
    v.find("input").val('');
    return v;
}

$('.table-row-add').on('click', '.add-td-link', (that) => {
    const table = $(that.target.parentNode).find('table');
    table.append(cloneRow(table));
});

$edittableID.on('click', '.table-elem-remove', function () {
    const table = $(this).closest('table');
    if(table.find('tbody tr').length - 1 >= 1) {
        $(this).closest('tr').remove();
    } else {
        table.append(cloneRow(table));
        $(this).closest('tr').remove();
    }
});


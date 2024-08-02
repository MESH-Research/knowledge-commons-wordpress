const $tableID = $('#role-table');
const $BTN = $('#export-btn');
const $EXPORT = $('#export');

const newTr = `
       <tr class='hidden'>
                             <td class="pt-3-half" contenteditable="false">
                                  <input type="search" name="role[0][name]" class="form-control autocomplete role-name">
                                  <button class="autocomplete-clear">
                                    <svg fill="#000000" height="24" viewBox="0 0 24 24" width="24" xmlns="https://www.w3.org/2000/svg">
                                      <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                                      <path d="M0 0h24v24H0z" fill="none" />
                                    </svg>
                                  </button>
                              </td>
                              <td class="pt-3-half" contenteditable="false">
                                  <input type='search'  id='artifact-autocomplete' name='role[0][artifact]' class='form-control autocomplete role-artifact' />
                                  <button class="autocomplete-clear">
                                    <svg fill="#000000" height="24" viewBox="0 0 24 24" width="24" xmlns="https://www.w3.org/2000/svg">
                                      <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                                      <path d="M0 0h24v24H0z" fill="none" />
                                    </svg>
                                  </button>
                              </td>
                            <td>
                              <span class="table-remove"><button type="button"
                                  class="btn btn-danger btn-rounded btn-sm my-0">Remove</button></span>
                            </td>
                          </tr>`;

$('.table-add').on('click', '.add-role-link', () => {
    const $clone = $tableID.find('tbody tr').last().clone(true).removeClass('hide table-line hidden');
    console.log($clone);
    if ($tableID.find('tbody tr').length === 0) {
        $('tbody').append(newTr);
    }
    let $length = $tableID.find('tbody tr').length-1;
    let name = $clone.find('.role-name').attr('name',$clone.find('.role-name').attr('name').replace(/([role]+\[[0-9]\])/, "role["+$tableID.find('tbody tr').length+"]"));
    let artifact = $clone.find('.role-artifact').attr('name',$clone.find('.role-artifact').attr('name').replace(/([role]+\[[0-9]\])/, "role["+$tableID.find('tbody tr').length+"]"));

    $tableID.append($clone);

    //console.log($clone.attr('name');
    let id = $clone.attr('name');


});

$tableID.on('click', '.table-remove', function () {
if($tableID.find('tbody tr').length - 1 >= 1) {
    $(this).closest('tr').remove();
} else {
    $(this).closest('tr').remove();
    $('tbody').append(newTr);
}
});

// A few jQuery helpers for exporting only
jQuery.fn.pop = [].pop;
jQuery.fn.shift = [].shift;

$BTN.on('click', () => {

    const $rows = $tableID.find('tr:not(:hidden)');
    const headers = [];
    const data = [];

    // Get the headers (add special header logic here)
    $($rows.shift()).find('th:not(:empty)').each(function () {

        headers.push($(this).text().toLowerCase());
    });

    // Turn all existing rows into a loopable array
    $rows.each(function () {
        const $td = $(this).find('td');
        const h = {};

        // Use the headers from earlier to name our hash keys
        headers.forEach((header, i) => {

            h[header] = $td.eq(i).text();
        });

        data.push(h);
    });

    // Output the result
    $EXPORT.text(JSON.stringify(data));
});

// const ajax = new XMLHttpRequest();
// let list = [];

//
// $('#artifact-autocomplete').keyup(function() {
//     console.log('changed');
//     console.log($(this).val().length);
//     if ($(this).val().length >= 3) {
//         console.log(">=3");
//         console.log($(this).val());
//         ajax.open("GET", "/wp-json/wp/v2/artifact?search="+$(this).val(), true);
//         ajax.onload = function() {
//             list = JSON.parse(ajax.responseText).map(function(i) { return i.title; });
//             console.log(list);
//             new Awesomplete(document.querySelector("#artifact-autocomplete"),{ list: list });
//         };
//     }
// });

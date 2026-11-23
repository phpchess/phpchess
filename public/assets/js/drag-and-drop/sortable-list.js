/**
 * Sortable lists (UI-09): lets a person put the items of a list in order by
 * dragging them, with a mouse or a finger.
 *
 * How to use it in a template:
 *
 *   <ol data-sortable-list data-order-field="division_order">
 *     <li data-item-id="12">First</li>
 *     <li data-item-id="7">Second</li>
 *   </ol>
 *   <input type="hidden" name="division_order" id="division_order">
 *
 * After every drag, the hidden field gets the item ids in their new order,
 * separated by commas ("7,12"), so the normal form sends it to the server.
 * Without JavaScript the page still works; it only can't be reordered.
 *
 * Needs /assets/vendor/sortablejs/Sortable.min.js loaded first.
 */

// --- Finding the lists on the page ---

function setUpAllSortableLists() {
    const lists = document.querySelectorAll('[data-sortable-list]');
    lists.forEach(setUpSortableList);
}

// --- Making one list sortable ---

function setUpSortableList(list) {
    const orderField = document.getElementById(list.dataset.orderField);

    Sortable.create(list, {
        animation: 150,
        onEnd: function () {
            writeOrderInto(orderField, list);
        },
    });

    writeOrderInto(orderField, list);
}

// --- Telling the form the new order ---

function writeOrderInto(orderField, list) {
    if (orderField === null) {
        return;
    }
    const ids = Array.from(list.children).map(function (item) {
        return item.dataset.itemId;
    });
    orderField.value = ids.join(',');
}

document.addEventListener('DOMContentLoaded', setUpAllSortableLists);

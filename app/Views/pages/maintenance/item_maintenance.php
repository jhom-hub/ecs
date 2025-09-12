<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Item Management</h4>
                <button class="btn btn-primary" id="addItemBtn">
                    Add Items
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="itemsTable" class="table table-sm table-striped table-bordered text-dark w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Name</th>
                                <th>Area Name</th>
                                <th>Building Name</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Combined Add/Update Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <form id="itemForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Items</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="item_id" name="item_id">
          <div class="row">
              <div class="col-md-6 form-group mb-3">
                  <label for="building_id">Building</label>
                  <select id="building_id" class="form-select" required></select>
              </div>
              <div class="col-md-6 form-group mb-3">
                  <label for="area_id">Area</label>
                  <select name="area_id" id="area_id" class="form-select" required></select>
              </div>
          </div>
          <hr>
          <h6>Items & Findings</h6>
          <div id="itemsContainer">
              <!-- Dynamic item rows will be added here -->
          </div>
          <button type="button" class="btn btn-success btn-sm mt-2" id="addNewItemRowBtn">
              <i class='bx bx-plus'></i> Add Another Item
          </button>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
$(document).ready(function () {
    const itemsTable = $('#itemsTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url: '<?= base_url('item_maintenance/getItems') ?>', type: 'POST' },
        columns: [
            { data: 'item_id' }, { data: 'item_name' }, { data: 'area_name' },
            { data: 'building_name' }, { data: 'created_at' }, { data: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    function loadBuildings(callback) {
        $.getJSON('<?= base_url('area_maintenance/getBuildingsForDropdown') ?>', function (res) {
            const selects = $('#building_id');
            selects.empty().append('<option value="">Select Building</option>');
            if (res.status === 'success') {
                res.data.forEach(bld => selects.append(`<option value="${bld.building_id}">${bld.building_name}</option>`));
            }
            if (callback) callback();
        });
    }

    function loadAreas(buildingId, selectedAreaId = null) {
        const areaSelect = $('#area_id');
        areaSelect.empty().append('<option value="">Loading...</option>');
        if (buildingId) {
            $.getJSON(`<?= base_url('item_maintenance/getAreasByBuilding/') ?>${buildingId}`, function (res) {
                areaSelect.empty().append('<option value="">Select Area</option>');
                if (res.status === 'success') {
                    res.data.forEach(area => areaSelect.append(`<option value="${area.area_id}">${area.area_name}</option>`));
                }
                if (selectedAreaId) areaSelect.val(selectedAreaId);
            });
        } else {
            areaSelect.empty().append('<option value="">Select a Building first</option>');
        }
    }

    loadBuildings();
    $('#building_id').on('change', function() { loadAreas($(this).val()); });

    function createItemRow(itemIndex, item = { name: '', findings: [] }) {
        let findingsHtml = '';
        item.findings.forEach(finding => {
            findingsHtml += `
                <span class="badge bg-secondary me-1 mb-1 finding-tag">
                    ${finding.findings_name || finding}
                    <input type="hidden" name="items[${itemIndex}][findings][]" value="${finding.findings_name || finding}">
                    <a href="#" class="text-white ms-1 remove-finding-tag">&times;</a>
                </span>
            `;
        });

        const itemRowHtml = `
            <div class="card item-row mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="items[${itemIndex}][name]" class="form-control" placeholder="Enter Item Name" value="${item.name}" required>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-danger btn-sm remove-item-btn">Remove Item</button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Findings for this Item</label>
                        <div class="findings-list mb-2">${findingsHtml}</div>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm new-finding-input" placeholder="Add a new finding and press Enter">
                        </div>
                    </div>
                </div>
            </div>
        `;
        return itemRowHtml;
    }

    function reindexItemRows() {
        $('#itemsContainer .item-row').each(function(index) {
            $(this).find('[name^="items"]').each(function() {
                const name = $(this).attr('name').replace(/items\[\d+\]/, `items[${index}]`);
                $(this).attr('name', name);
            });
        });
    }
    
    $('#addNewItemRowBtn').on('click', function() {
        const newIndex = $('#itemsContainer .item-row').length;
        $('#itemsContainer').append(createItemRow(newIndex));
    });

    $('#itemsContainer').on('click', '.remove-item-btn', function() {
        if ($('#itemsContainer .item-row').length > 1) {
            $(this).closest('.item-row').remove();
            reindexItemRows();
        } else {
            Swal.fire('Cannot Remove', 'You must have at least one item.', 'warning');
        }
    });

    $('#itemsContainer').on('keypress', '.new-finding-input', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            const findingName = $(this).val().trim();
            const itemIndex = $(this).closest('.item-row').index();
            if (findingName) {
                const findingTagHtml = `
                    <span class="badge bg-secondary me-1 mb-1 finding-tag">
                        ${findingName}
                        <input type="hidden" name="items[${itemIndex}][findings][]" value="${findingName}">
                        <a href="#" class="text-white ms-1 remove-finding-tag">&times;</a>
                    </span>`;
                $(this).closest('.mt-3').find('.findings-list').append(findingTagHtml);
                $(this).val('');
            }
        }
    });

    $('#itemsContainer').on('click', '.remove-finding-tag', function(e) {
        e.preventDefault();
        $(this).parent().remove();
    });

    $('#addItemBtn').on('click', function() {
        $('#itemForm')[0].reset();
        $('#item_id').val('');
        $('#modalTitle').text('Add Items');
        $('#itemsContainer').empty().append(createItemRow(0));
        $('#area_id').empty().append('<option value="">Select a Building first</option>');
        $('#addNewItemRowBtn').show();
        $('#itemModal').modal('show');
    });

    $('#itemForm').submit(function (e) {
        e.preventDefault();
        const url = $('#item_id').val() ? '<?= base_url('item_maintenance/updateItem') ?>' : '<?= base_url('item_maintenance/addItem') ?>';
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');
        $.post(url, $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#itemModal').modal('hide');
                itemsTable.ajax.reload();
                Swal.fire('Success!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Request failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Save Changes'));
    });

    window.editItem = function(itemId) {
        $.getJSON(`<?= base_url('item_maintenance/details/') ?>${itemId}`, function (res) {
            if (res.status === 'success') {
                const item = res.data;
                $('#itemForm')[0].reset();
                $('#item_id').val(item.item_id);
                $('#modalTitle').text('Update Item');
                $('#itemsContainer').empty().append(createItemRow(0, { name: item.item_name, findings: item.findings }));
                $('#addNewItemRowBtn').hide();
                
                loadBuildings(function() {
                    $('#building_id').val(item.building_id);
                    loadAreas(item.building_id, item.area_id);
                });

                $('#itemModal').modal('show');
            } else {
                Swal.fire('Error!', res.message || 'Unable to fetch item details.', 'error');
            }
        });
    }

    window.deleteItem = function(itemId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This item and all its findings will be permanently deleted.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`<?= base_url('item_maintenance/deleteItem/') ?>${itemId}`, function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Deleted!', res.message, 'success');
                        itemsTable.ajax.reload();
                    } else {
                        Swal.fire('Error!', res.message || 'Deletion failed.', 'error');
                    }
                }, 'json');
            }
        });
    }
});
</script>


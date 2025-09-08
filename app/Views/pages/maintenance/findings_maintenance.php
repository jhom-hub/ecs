<div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Findings Type Management</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFindingModal">
                        Add Finding Type
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="findingsTable" class="table table-sm table-striped table-bordered text-dark w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Finding Name</th>
                                    <th>Item</th>
                                    <th>Area</th>
                                    <th>Building</th>
                                    <th>Created At</th>
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
</div>

<div class="modal fade" id="addFindingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="addFindingForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Finding Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="form-group mb-3">
              <label for="add_building_id">Building</label>
              <select id="add_building_id" class="form-control" required></select>
          </div>
          <div class="form-group mb-3">
              <label for="add_area_id">Area</label>
              <select id="add_area_id" class="form-control" required></select>
          </div>
          <div class="form-group mb-3">
              <label for="add_item_id">Item</label>
              <select name="item_id" id="add_item_id" class="form-control" required></select>
          </div>
          <div class="form-group">
              <label for="findings_name">Finding Name</label>
              <input type="text" name="findings_name" class="form-control" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add Finding Type</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="updateFindingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="updateFindingForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Finding Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="update_findings_id" name="findings_id">
          <div class="form-group mb-3">
              <label for="update_building_id">Building</label>
              <select id="update_building_id" class="form-control" required></select>
          </div>
          <div class="form-group mb-3">
              <label for="update_area_id">Area</label>
              <select id="update_area_id" class="form-control" required></select>
          </div>
          <div class="form-group mb-3">
              <label for="update_item_id">Item</label>
              <select name="item_id" id="update_item_id" class="form-control" required></select>
          </div>
          <div class="form-group">
              <label for="update_findings_name">Finding Name</label>
              <input type="text" id="update_findings_name" name="findings_name" class="form-control" required>
          </div>
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
    const findingsTable = $('#findingsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('findings_type_maintenance/getFindingsTypes') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'findings_id' },
            { data: 'findings_name' },
            { data: 'item_name' },
            { data: 'area_name' },
            { data: 'building_name' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    // --- Dependent Dropdown Logic ---
    function loadBuildings(callback, buildingSelectId = '#add_building_id, #update_building_id') {
        $.getJSON('<?= base_url('area_maintenance/getBuildingsForDropdown') ?>', function (res) {
            const selects = $(buildingSelectId);
            selects.empty().append('<option value="">Select Building</option>');
            if (res.status === 'success') {
                res.data.forEach(bld => selects.append(`<option value="${bld.building_id}">${bld.building_name}</option>`));
            }
            if (callback) callback();
        });
    }

    function loadAreas(buildingId, areaSelectId, selectedAreaId = null) {
        const areaSelect = $(`#${areaSelectId}`);
        const itemSelect = $(`#${areaSelectId.replace('area', 'item')}`);
        areaSelect.empty().append('<option value="">Loading...</option>');
        itemSelect.empty().append('<option value="">Select Item</option>');
        if (buildingId) {
            $.getJSON(`<?= base_url('item_maintenance/getAreasByBuilding/') ?>${buildingId}`, function (res) {
                areaSelect.empty().append('<option value="">Select Area</option>');
                if (res.status === 'success') {
                    res.data.forEach(area => areaSelect.append(`<option value="${area.area_id}">${area.area_name}</option>`));
                }
                if (selectedAreaId) areaSelect.val(selectedAreaId).trigger('change');
            });
        } else {
            areaSelect.empty().append('<option value="">Select Area</option>');
        }
    }

    function loadItems(areaId, itemSelectId, selectedItemId = null) {
        const itemSelect = $(`#${itemSelectId}`);
        itemSelect.empty().append('<option value="">Loading...</option>');
        if (areaId) {
            $.getJSON(`<?= base_url('checksheet_maintenance/getItemsByArea/') ?>${areaId}`, function (res) {
                itemSelect.empty().append('<option value="">Select Item</option>');
                if (res.status === 'success') {
                    res.data.forEach(item => itemSelect.append(`<option value="${item.item_id}">${item.item_name}</option>`));
                }
                if (selectedItemId) itemSelect.val(selectedItemId);
            });
        } else {
            itemSelect.empty().append('<option value="">Select Item</option>');
        }
    }

    loadBuildings();

    // Event Listeners
    $('#add_building_id, #update_building_id').on('change', function() {
        const areaSelectId = $(this).attr('id').replace('building', 'area');
        loadAreas($(this).val(), areaSelectId);
    });
    $('#add_area_id, #update_area_id').on('change', function() {
        const itemSelectId = $(this).attr('id').replace('area', 'item');
        loadItems($(this).val(), itemSelectId);
    });


    // --- CRUD Logic ---
    $('#addFindingForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Adding...');
        $.post('<?= base_url('findings_type_maintenance/addFindingsType') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#addFindingModal').modal('hide');
                $('#addFindingForm')[0].reset();
                findingsTable.ajax.reload();
                Swal.fire('Added!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Add failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Add Finding Type'));
    });

    $('#updateFindingForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');
        $.post('<?= base_url('findings_type_maintenance/updateFindingsType') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#updateFindingModal').modal('hide');
                findingsTable.ajax.reload();
                Swal.fire('Updated!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Update failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Save Changes'));
    });

    // **FIX:** Moved these functions INSIDE the document.ready block.
    // They are attached to the `window` object to be accessible by the `onclick` attribute in the HTML buttons.
    window.editFinding = function(findingId) {
        $.getJSON(`<?= base_url('findings_type_maintenance/details/') ?>${findingId}`, function (res) {
            if (res.status === 'success') {
                const finding = res.data;
                $('#update_findings_id').val(finding.findings_id);
                $('#update_findings_name').val(finding.findings_name);

                // This call will now work correctly
                loadBuildings(() => {
                    $('#update_building_id').val(finding.building_id);
                    loadAreas(finding.building_id, 'update_area_id', finding.area_id);
                    loadItems(finding.area_id, 'update_item_id', finding.item_id);
                }, '#update_building_id');
                
                $('#updateFindingModal').modal('show');
            } else {
                Swal.fire('Error!', res.message || 'Unable to fetch finding details.', 'error');
            }
        });
    }

    window.deleteFinding = function(findingId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This finding type will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`<?= base_url('findings_type_maintenance/deleteFindingsType/') ?>${findingId}`, function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Deleted!', res.message, 'success');
                        $('#findingsTable').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Error!', res.message, 'Deletion failed.', 'error');
                    }
                }, 'json');
            }
        });
    }
});
</script>
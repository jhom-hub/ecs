<div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Findings Type Management</h4>
                    <button class="btn btn-primary" id="addFindingBtn">
                        Add Findings
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

<!-- Combined Add/Update Modal -->
<div class="modal fade" id="findingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="findingForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Findings</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="findings_id" name="findings_id">
          <div class="form-group mb-3">
              <label for="building_id">Building</label>
              <select id="building_id" class="form-select" required></select>
          </div>
          <div class="form-group mb-3">
              <label for="area_id">Area</label>
              <select id="area_id" class="form-select" required></select>
          </div>
          <div class="form-group mb-3">
              <label for="item_id">Item</label>
              <select name="item_id" id="item_id" class="form-select" required></select>
          </div>
          <hr>
          <h6>Findings</h6>
          <div id="findingsContainer">
              <!-- Dynamic finding rows will be added here -->
          </div>
          <button type="button" class="btn btn-success btn-sm mt-2" id="addNewFindingRowBtn">
              <i class='bx bx-plus'></i> Add Another Finding
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
    const findingsTable = $('#findingsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('findings_type_maintenance/getFindingsTypes') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'findings_id' }, { data: 'findings_name' },
            { data: 'item_name' }, { data: 'area_name' },
            { data: 'building_name' }, { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    // --- Dependent Dropdown Logic ---
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
        $('#item_id').empty().append('<option value="">Select Area first</option>');
        if (buildingId) {
            $.getJSON(`<?= base_url('item_maintenance/getAreasByBuilding/') ?>${buildingId}`, function (res) {
                areaSelect.empty().append('<option value="">Select Area</option>');
                if (res.status === 'success') {
                    res.data.forEach(area => areaSelect.append(`<option value="${area.area_id}">${area.area_name}</option>`));
                }
                if (selectedAreaId) areaSelect.val(selectedAreaId).trigger('change');
            });
        } else {
            areaSelect.empty().append('<option value="">Select a Building first</option>');
        }
    }

    function loadItems(areaId, selectedItemId = null) {
        const itemSelect = $('#item_id');
        itemSelect.empty().append('<option value="">Loading...</option>');
        if (areaId) {
            // FIXED: This URL now correctly points to the route you provided.
            $.getJSON(`<?= base_url('findings_maintenance/getItemsByArea/') ?>${areaId}`, function (res) {
                itemSelect.empty().append('<option value="">Select Item</option>');
                if (res.status === 'success') {
                    res.data.forEach(item => itemSelect.append(`<option value="${item.item_id}">${item.item_name}</option>`));
                }
                if (selectedItemId) itemSelect.val(selectedItemId);
            });
        } else {
            itemSelect.empty().append('<option value="">Select an Area first</option>');
        }
    }

    loadBuildings();

    $('#building_id').on('change', function() { loadAreas($(this).val()); });
    $('#area_id').on('change', function() { loadItems($(this).val()); });
    
    function addFindingRow(findingName = '') {
        const findingRowHtml = `
            <div class="row finding-row mb-2">
                <div class="col-9">
                    <input type="text" name="findings_name[]" class="form-control form-control-sm" placeholder="Finding Name" value="${findingName}" required>
                </div>
                <div class="col-3">
                    <button type="button" class="btn btn-danger btn-sm remove-finding-btn w-100">Remove</button>
                </div>
            </div>
        `;
        $('#findingsContainer').append(findingRowHtml);
    }
    
    $('#addNewFindingRowBtn').on('click', () => addFindingRow());
    $('#findingsContainer').on('click', '.remove-finding-btn', function() {
        if ($('.finding-row').length > 1) {
            $(this).closest('.finding-row').remove();
        } else {
            Swal.fire('Cannot Remove', 'You must have at least one finding.', 'warning');
        }
    });

    $('#addFindingBtn').on('click', function() {
        $('#findingForm')[0].reset();
        $('#findings_id').val('');
        $('#modalTitle').text('Add Findings');
        $('#findingsContainer').empty();
        addFindingRow();
        $('#area_id, #item_id').empty().append('<option value="">Select parent first</option>');
        $('#addNewFindingRowBtn').show();
        $('#findingModal').modal('show');
    });

    $('#findingForm').submit(function (e) {
        e.preventDefault();
        const url = $('#findings_id').val() ? '<?= base_url('findings_type_maintenance/updateFindingsType') ?>' : '<?= base_url('findings_type_maintenance/addFindingsType') ?>';
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');
        $.post(url, $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#findingModal').modal('hide');
                findingsTable.ajax.reload();
                Swal.fire('Success!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Request failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Save Changes'));
    });

    window.editFinding = function(findingId) {
        $.getJSON(`<?= base_url('findings_type_maintenance/details/') ?>${findingId}`, function (res) {
            if (res.status === 'success') {
                const finding = res.data;
                $('#findingForm')[0].reset();
                $('#findings_id').val(finding.findings_id);
                $('#modalTitle').text('Update Finding Type');

                $('#findingsContainer').empty();
                addFindingRow(finding.findings_name);

                $('#addNewFindingRowBtn').hide();
                $('.remove-finding-btn').hide();

                loadBuildings(() => {
                    $('#building_id').val(finding.building_id);
                    loadAreas(finding.building_id, finding.area_id);
                    loadItems(finding.area_id, finding.item_id);
                });
                
                $('#findingModal').modal('show');
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
                        Swal.fire('Error!', res.message || 'Deletion failed.', 'error');
                    }
                }, 'json');
            }
        });
    }
});
</script>


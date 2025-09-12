<div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Checksheet Management</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addChecksheetModal">
                        Add Entry
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="checksheetsTable" class="table table-sm table-striped table-bordered text-dark w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Building</th>
                                    <th>Area</th>
                                    <th>Item</th>
                                    <th>DRI</th>
                                    <th>Finding</th>
                                    <th>Status</th>
                                    <th>Date</th>
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

<!-- ADD/UPDATE MODAL -->
<div class="modal fade" id="checksheetModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="checksheetForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Checksheet Entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="checksheet_id" name="checksheet_id">
          <div class="row">
              <!-- Location Column -->
              <div class="col-md-6">
                  <h5>Location</h5>
                  <div class="form-group mb-3">
                      <label>Building</label>
                      <select name="building_id" id="building_id" class="form-control" required></select>
                  </div>
                  <div class="form-group mb-3">
                      <label>Area</label>
                      <select name="area_id" id="area_id" class="form-control" required></select>
                  </div>
                  <div class="form-group mb-3">
                      <label>Item</label>
                      <select name="item_id" id="item_id" class="form-control" required></select>
                  </div>
              </div>
              <!-- Personnel & Finding Column -->
              <div class="col-md-6">
                  <h5>Personnel</h5>
                  <div class="form-group mb-3">
                      <label>Department</label>
                      <select id="department_id" class="form-control" required></select>
                  </div>
                  <div class="form-group mb-3">
                      <label>Division</label>
                      <select id="division_id" class="form-control" required></select>
                  </div>
                  <div class="form-group mb-3">
                      <label>Section</label>
                      <select id="section_id" class="form-control" required></select>
                  </div>
                  <div class="form-group mb-3">
                      <label>DRI</label>
                      <select name="dri_id" id="dri_id" class="form-control" required></select>
                  </div>
              </div>
          </div>
          <hr>
          <div class="row">
              <div class="col-md-6">
                  <div class="form-group mb-3">
                      <label>Finding Type</label>
                      <select name="finding_id" id="finding_id" class="form-control" required></select>
                  </div>
              </div>
              <div class="col-md-6">
                  <div class="form-group mb-3">
                      <label>Status</label>
                      <input type="text" name="status" id="status" class="form-control" required>
                  </div>
              </div>
              <div class="col-12">
                  <div class="form-group">
                      <label>Remarks</label>
                      <textarea name="remarks" id="remarks" class="form-control"></textarea>
                  </div>
              </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save Entry</button>
      </div>
    </form>
  </div>
</div>

<script>
$(document).ready(function () {
    const checksheetsTable = $('#checksheetsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('checksheet_maintenance/getChecksheets') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'checksheet_id' },
            { data: 'building_name' },
            { data: 'area_name' },
            { data: 'item_name' },
            { data: 'dri_name' },
            { data: 'findings_name' },
            { data: 'status' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    // --- Dependent Dropdown Logic ---

    // Location Dropdowns
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
        const itemSelect = $('#item_id');
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

    function loadItems(areaId, selectedItemId = null) {
        const itemSelect = $('#item_id');
        itemSelect.empty().append('<option value="">Loading...</option>');
        if (areaId) {
            $.getJSON(`<?= base_url('checksheet_maintenance/getItemsByArea/') ?>${areaId}`, function (res) {
                itemSelect.empty().append('<option value="">Select Item</option>');
                if (res.status === 'success') {
                    res.data.forEach(item => itemSelect.append(`<option value="${item.item_id}">${item.item_name}</option>`));
                }
                if (selectedItemId) itemSelect.val(selectedItemId).trigger('change');
            });
        } else {
            itemSelect.empty().append('<option value="">Select Item</option>');
        }
    }

    // Personnel Dropdowns
    function loadDepartments(callback) {
        $.getJSON('<?= base_url('division_maintenance/getDepartmentsForDropdown') ?>', function (res) {
            const selects = $('#department_id');
            selects.empty().append('<option value="">Select Department</option>');
            if (res.status === 'success') {
                res.data.forEach(dept => selects.append(`<option value="${dept.department_id}">${dept.department_name}</option>`));
            }
            if (callback) callback();
        });
    }

    function loadDivisions(departmentId, selectedDivisionId = null) {
        const divisionSelect = $('#division_id'), sectionSelect = $('#section_id'), driSelect = $('#dri_id');
        divisionSelect.empty().append('<option value="">Loading...</option>');
        sectionSelect.empty().append('<option value="">Select Section</option>');
        driSelect.empty().append('<option value="">Select DRI</option>');
        if (departmentId) {
            $.getJSON(`<?= base_url('section_maintenance/getDivisionsByDepartment/') ?>${departmentId}`, function (res) {
                divisionSelect.empty().append('<option value="">Select Division</option>');
                if (res.status === 'success') {
                    res.data.forEach(div => divisionSelect.append(`<option value="${div.division_id}">${div.division_name}</option>`));
                }
                if (selectedDivisionId) divisionSelect.val(selectedDivisionId).trigger('change');
            });
        } else {
            divisionSelect.empty().append('<option value="">Select Division</option>');
        }
    }

    function loadSections(divisionId, selectedSectionId = null) {
        const sectionSelect = $('#section_id'), driSelect = $('#dri_id');
        sectionSelect.empty().append('<option value="">Loading...</option>');
        driSelect.empty().append('<option value="">Select DRI</option>');
        if (divisionId) {
            $.getJSON(`<?= base_url('dri_maintenance/getSectionsByDivision/') ?>${divisionId}`, function (res) {
                sectionSelect.empty().append('<option value="">Select Section</option>');
                if (res.status === 'success') {
                    res.data.forEach(sec => sectionSelect.append(`<option value="${sec.section_id}">${sec.section_name}</option>`));
                }
                if (selectedSectionId) sectionSelect.val(selectedSectionId).trigger('change');
            });
        } else {
            sectionSelect.empty().append('<option value="">Select Section</option>');
        }
    }

    function loadDris(sectionId, selectedDriId = null) {
        const driSelect = $('#dri_id');
        driSelect.empty().append('<option value="">Loading...</option>');
        if (sectionId) {
            $.getJSON(`<?= base_url('checksheet_maintenance/getDrisBySection/') ?>${sectionId}`, function (res) {
                driSelect.empty().append('<option value="">Select DRI</option>');
                if (res.status === 'success') {
                    res.data.forEach(dri => driSelect.append(`<option value="${dri.dri_id}">${dri.fullname}</option>`));
                }
                if (selectedDriId) driSelect.val(selectedDriId);
            });
        } else {
            driSelect.empty().append('<option value="">Select DRI</option>');
        }
    }
    
    // NEW/UPDATED Findings Dropdown Logic
    function loadFindings(itemId, selectedFindingId = null) {
        const findingSelect = $('#finding_id');
        findingSelect.empty().append('<option value="">Loading...</option>');
        if (itemId) {
            $.getJSON(`<?= base_url('checksheet_maintenance/getFindingsByItem/') ?>${itemId}`, function (res) {
                findingSelect.empty().append('<option value="">Select Finding</option>');
                if (res.status === 'success') {
                    res.data.forEach(f => findingSelect.append(`<option value="${f.findings_id}">${f.findings_name}</option>`));
                }
                if (selectedFindingId) findingSelect.val(selectedFindingId);
            });
        } else {
            findingSelect.empty().append('<option value="">Select an Item first</option>');
        }
    }


    // Initial loads
    loadBuildings();
    loadDepartments();
    
    // Event Listeners
    $('#building_id').on('change', function() { loadAreas($(this).val()); });
    $('#area_id').on('change', function() { loadItems($(this).val()); });
    $('#item_id').on('change', function() { loadFindings($(this).val()); }); // NEW
    $('#department_id').on('change', function() { loadDivisions($(this).val()); });
    $('#division_id').on('change', function() { loadSections($(this).val()); });
    $('#section_id').on('change', function() { loadDris($(this).val()); });


    // --- Form Submission ---
    $('#checksheetForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const url = $('#checksheet_id').val() ? '<?= base_url('checksheet_maintenance/updateChecksheet') ?>' : '<?= base_url('checksheet_maintenance/addChecksheet') ?>';
        $btn.prop('disabled', true).text('Saving...');
        
        $.post(url, $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#checksheetModal').modal('hide');
                checksheetsTable.ajax.reload();
                Swal.fire('Success!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Request failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Save Entry'));
    });

});

// Add Modal Trigger
$('[data-bs-target="#addChecksheetModal"]').on('click', function() {
    $('#checksheetForm')[0].reset();
    $('#checksheet_id').val('');
    $('#modalTitle').text('Add Checksheet Entry');
    // Clear dropdowns
    $('#area_id, #item_id, #division_id, #section_id, #dri_id, #finding_id').empty();
    $('#checksheetModal').modal('show');
});


// Edit Modal Trigger
function editChecksheet(id) {
    $.getJSON(`<?= base_url('checksheet_maintenance/details/') ?>${id}`, function (res) {
        if (res.status === 'success') {
            const data = res.data;
            $('#checksheet_id').val(data.checksheet_id);
            $('#status').val(data.status);
            $('#remarks').val(data.remarks);
            
            // Load and set all dropdowns
            loadBuildings(() => {
                $('#building_id').val(data.building_id);
                loadAreas(data.building_id, data.area_id);
                loadItems(data.area_id, data.item_id);
                loadFindings(data.item_id, data.finding_id); // UPDATED
            });
            loadDepartments(() => {
                $('#department_id').val(data.department_id);
                loadDivisions(data.department_id, data.division_id);
                loadSections(data.division_id, data.section_id);
                loadDris(data.section_id, data.dri_id);
            });

            $('#modalTitle').text('Update Checksheet Entry');
            $('#checksheetModal').modal('show');
        } else {
            Swal.fire('Error!', res.message, 'error');
        }
    });
}

// Delete Logic
function deleteChecksheet(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This entry will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(`<?= base_url('checksheet_maintenance/deleteChecksheet/') ?>${id}`, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Deleted!', res.message, 'success');
                    $('#checksheetsTable').DataTable().ajax.reload();
                } else {
                    Swal.fire('Error!', res.message, 'error');
                }
            }, 'json');
        }
    });
}
</script>
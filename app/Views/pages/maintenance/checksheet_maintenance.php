<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Checksheet Management</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#checksheetModal" id="addChecksheetBtn">
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
                                <!-- REMOVED: Item Column -->
                                <th>Auditor</th>
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
                    <div class="col-md-6">
                        <h5>Location</h5>
                        <div class="form-group mb-3">
                            <label>Building</label>
                            <select name="building_id" id="building_id" class="form-select" required></select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Area</label>
                            <select name="area_id" id="area_id" class="form-select" required></select>
                        </div>
                        <!-- REMOVED: Item Dropdown -->
                    </div>
                    <div class="col-md-6">
                        <h5>Personnel</h5>
                        <div class="form-group mb-3">
                            <label>Auditor</label>
                            <select name="dri_id" id="dri_id" class="form-select" required></select>
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
        // REMOVED: Item column data
        columns: [
            { data: 'checksheet_id' },
            { data: 'building_name' },
            { data: 'area_name' },
            { data: 'dri_name' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    function loadBuildings(callback) {
        $.getJSON('<?= base_url('area_maintenance/getBuildingsForDropdown') ?>', res => {
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
        areaSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
        
        // Clear auditor dropdown
        $('#dri_id').empty().append('<option value="">Select Area first</option>');

        if (buildingId) {
            $.getJSON(`<?= base_url('item_maintenance/getAreasByBuilding/') ?>${buildingId}`, res => {
                areaSelect.empty().append('<option value="">Select Area</option>').prop('disabled', false);
                if (res.status === 'success') {
                    res.data.forEach(area => areaSelect.append(`<option value="${area.area_id}">${area.area_name}</option>`));
                }
                if (selectedAreaId) areaSelect.val(selectedAreaId).trigger('change');
            });
        } else {
            areaSelect.empty().append('<option value="">Select a Building first</option>').prop('disabled', false);
        }
    }
    
    function loadAuditors(areaId, selectedAuditorId = null) {
        const auditorSelect = $('#dri_id');
        auditorSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
        if (areaId) {
            $.getJSON(`<?= base_url('checksheet_maintenance/getAuditorsByArea/') ?>${areaId}`, res => {
                auditorSelect.empty().append('<option value="">Select Auditor</option>').prop('disabled', false);
                if (res.status === 'success') {
                    res.data.forEach(auditor => {
                        auditorSelect.append(`<option value="${auditor.user_id}">${auditor.fullname}</option>`);
                    });
                }
                if (selectedAuditorId) {
                    auditorSelect.val(selectedAuditorId);
                }
            });
        } else {
            auditorSelect.empty().append('<option value="">Select an Area first</option>').prop('disabled', false);
        }
    }

    loadBuildings();
    
    $('#building_id').on('change', function() { loadAreas($(this).val()); });
    // UPDATED: Area change now only loads Auditors
    $('#area_id').on('change', function() { 
        loadAuditors($(this).val());
    });
    
    $('#checksheetForm').submit(function (e) {
        e.preventDefault();
        const url = $('#checksheet_id').val() ? '<?= base_url('checksheet_maintenance/updateChecksheet') ?>' : '<?= base_url('checksheet_maintenance/addChecksheet') ?>';
        $.post(url, $(this).serialize(), res => {
            if (res.status === 'success') {
                $('#checksheetModal').modal('hide');
                checksheetsTable.ajax.reload();
                Swal.fire('Success!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Request failed.', 'error');
            }
        }, 'json');
    });

    $('#addChecksheetBtn').on('click', function() {
        $('#checksheetForm')[0].reset();
        $('#checksheet_id').val('');
        $('#modalTitle').text('Add Checksheet Entry');
        $('#area_id, #dri_id').empty()
            .append('<option value="">Please select the parent category first</option>');
    });


    window.editChecksheet = function(id) {
        $.getJSON(`<?= base_url('checksheet_maintenance/details/') ?>${id}`, res => {
            if (res.status === 'success') {
                const data = res.data;
                $('#checksheetForm')[0].reset();
                
                $('#checksheet_id').val(data.checksheet_id);
                
                loadBuildings(() => {
                    $('#building_id').val(data.building_id);
                    // Load areas and then auditors in sequence
                    loadAreas(data.building_id, data.area_id);
                    loadAuditors(data.area_id, data.dri_id);
                });

                $('#modalTitle').text('Update Checksheet Entry');
                $('#checksheetModal').modal('show');
            } else {
                Swal.fire('Error!', res.message, 'error');
            }
        });
    }

    window.deleteChecksheet = function(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This entry will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
        }).then(result => {
            if (result.isConfirmed) {
                $.post(`<?= base_url('checksheet_maintenance/deleteChecksheet/') ?>${id}`, res => {
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
});
</script>


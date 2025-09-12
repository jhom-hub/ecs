<div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Division Management</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDivisionModal">
                        Add Division
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="divisionsTable" class="table table-sm table-striped table-bordered text-dark w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Division Name</th>
                                    <th>Department Name</th>
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
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addDivisionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="addDivisionForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Division</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="form-group mb-3">
              <label for="department_id">Department</label>
              <select name="department_id" id="add_department_id" class="form-control" required>
                  <option value="">Select Department</option>
              </select>
          </div>
          <div class="form-group">
              <label for="division_name">Division Name</label>
              <input type="text" name="division_name" class="form-control" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add Division</button>
      </div>
    </form>
  </div>
</div>

<!-- UPDATE MODAL -->
<div class="modal fade" id="updateDivisionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="updateDivisionForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Division</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="update_division_id" name="division_id">
          <div class="form-group mb-3">
              <label for="update_department_id">Department</label>
              <select name="department_id" id="update_department_id" class="form-control" required>
                  <option value="">Select Department</option>
              </select>
          </div>
          <div class="form-group">
              <label for="update_division_name">Division Name</label>
              <input type="text" id="update_division_name" name="division_name" class="form-control" required>
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
    const divisionsTable = $('#divisionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('division_maintenance/getDivisions') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'division_id' },
            { data: 'division_name' },
            { data: 'department_name' },
            { data: 'created_at' },
            { data: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    // Function to load departments into select dropdowns
    function loadDepartments() {
        $.getJSON('<?= base_url('division_maintenance/getDepartmentsForDropdown') ?>', function (res) {
            if (res.status === 'success') {
                const selects = $('#add_department_id, #update_department_id');
                selects.empty().append('<option value="">Select Department</option>');
                res.data.forEach(function(dept) {
                    selects.append(`<option value="${dept.department_id}">${dept.department_name}</option>`);
                });
            }
        });
    }
    
    loadDepartments();

    // Add Division
    $('#addDivisionForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Adding...');
        $.post('<?= base_url('division_maintenance/addDivision') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#addDivisionModal').modal('hide');
                $('#addDivisionForm')[0].reset();
                divisionsTable.ajax.reload();
                Swal.fire('Added!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Add failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Add Division'));
    });

    // Update Division
    $('#updateDivisionForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');
        $.post('<?= base_url('division_maintenance/updateDivision') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#updateDivisionModal').modal('hide');
                divisionsTable.ajax.reload();
                Swal.fire('Updated!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Update failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Save Changes'));
    });
});

// Trigger Edit Modal
function editDivision(divisionId) {
    $.getJSON(`<?= base_url('division_maintenance/details/') ?>${divisionId}`, function (res) {
        if (res.status === 'success') {
            const div = res.data;
            $('#update_division_id').val(div.division_id);
            $('#update_division_name').val(div.division_name);
            $('#update_department_id').val(div.department_id); // Set the selected department
            $('#updateDivisionModal').modal('show');
        } else {
            Swal.fire('Error!', res.message || 'Unable to fetch division details.', 'error');
        }
    });
}

// Delete Division with SweetAlert
function deleteDivision(divisionId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This division will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#e3342f'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(`<?= base_url('division_maintenance/deleteDivision/') ?>${divisionId}`, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Deleted!', res.message, 'success');
                    $('#divisionsTable').DataTable().ajax.reload();
                } else {
                    Swal.fire('Error!', res.message || 'Deletion failed.', 'error');
                }
            }, 'json');
        }
    });
}
</script>
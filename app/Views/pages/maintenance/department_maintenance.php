<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Department Maintenance</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                    Add Department
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="departmentsTable" class="table table-sm table-striped table-bordered text-dark w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
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

<!-- ADD MODAL -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="addDepartmentForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Department</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="form-group">
              <label for="department_name">Department Name</label>
              <input type="text" name="department_name" class="form-control" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add Department</button>
      </div>
    </form>
  </div>
</div>

<!-- UPDATE MODAL -->
<div class="modal fade" id="updateDepartmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="updateDepartmentForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Department</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="update_department_id" name="department_id">
          <div class="form-group">
              <label for="update_department_name">Department Name</label>
              <input type="text" id="update_department_name" name="department_name" class="form-control" required>
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
    const departmentsTable = $('#departmentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('department_maintenance/getDepartments') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'department_id' },
            { data: 'department_name' },
            { data: 'created_at' },
            { data: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    // Add Department
    $('#addDepartmentForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Adding...');
        $.post('<?= base_url('department_maintenance/addDepartment') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#addDepartmentModal').modal('hide');
                $('#addDepartmentForm')[0].reset();
                departmentsTable.ajax.reload();
                Swal.fire('Added!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Add failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Add Department'));
    });

    // Update Department
    $('#updateDepartmentForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');
        $.post('<?= base_url('department_maintenance/updateDepartment') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#updateDepartmentModal').modal('hide');
                departmentsTable.ajax.reload();
                Swal.fire('Updated!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Update failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Save Changes'));
    });
});

// Trigger Edit Modal
function editDepartment(departmentId) {
    // Note: You need a route like 'department_maintenance/details/(:num)' for this to work
    $.getJSON(`<?= base_url('department_maintenance/details/') ?>${departmentId}`, function (res) {
        if (res.status === 'success') {
            const dept = res.data;
            $('#update_department_id').val(dept.department_id);
            $('#update_department_name').val(dept.department_name);
            $('#updateDepartmentModal').modal('show');
        } else {
            Swal.fire('Error!', res.message || 'Unable to fetch department details.', 'error');
        }
    });
}

// Delete Department with SweetAlert
function deleteDepartment(departmentId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This department will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#e3342f'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(`<?= base_url('department_maintenance/deleteDepartment/') ?>${departmentId}`, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Deleted!', res.message, 'success');
                    $('#departmentsTable').DataTable().ajax.reload();
                } else {
                    Swal.fire('Error!', res.message || 'Deletion failed.', 'error');
                }
            }, 'json');
        }
    });
}
</script>
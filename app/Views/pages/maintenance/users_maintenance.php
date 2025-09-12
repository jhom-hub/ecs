<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><?= $pageTitle ?? 'User Management' ?></h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usersAddModal">
                    <i class="mdi mdi-account-plus"></i> Add User
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="usersTable" class="table table-sm table-striped table-bordered text-dark w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Status</th>
                                <th>Role</th>
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

<div class="modal fade" id="usersAddModal" tabindex="-1" aria-labelledby="usersAddModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="addUserForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Account by Employee ID</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <p class="text-muted">Enter an Employee ID to sync their data from the master database.</p>
          <div class="form-group mb-3">
              <label for="employee_id">Employee ID</label>
              <input type="text" id="employee_id" name="employee_id" class="form-control" required>
          </div>
          <hr/>
          <p class="text-muted">Assign their organizational role and structure.</p>
          <div class="form-group mb-3">
              <label for="add_department_id">Department</label>
              <select name="department_id" id="add_department_id" class="form-select" required>
                  <option value="">Select Department</option>
              </select>
          </div>
          <div class="form-group mb-3">
              <label for="add_division_id">Division</label>
              <select name="division_id" id="add_division_id" class="form-select" required>
                  <option value="">Select Division</option>
              </select>
          </div>
          <div class="form-group mb-3">
              <label for="add_section_id">Section</label>
              <select name="section_id" id="add_section_id" class="form-select" required>
                  <option value="">Select Section</option>
              </select>
          </div>
          <div class="form-group">
              <label for="add_role">Role</label>
              <select id="add_role" name="role" class="form-select" required>
                  <option value="">Select Role</option>
                  <option value="ADMINISTRATOR">ADMINISTRATOR</option>
                  <option value="AUDITOR">AUDITOR</option>
                  <option value="GA">GA</option>
                  <option value="DRI">DRI</option>
                  <option value="GUEST">GUEST</option>
              </select>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add Account</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="usersUpdateModal" tabindex="-1" aria-labelledby="usersUpdateModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="updateUserForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="update_user_id" name="user_id">
          <div class="form-group mb-2">
              <label>Employee ID:</label>
              <p id="update_employee_id" class="form-control-plaintext ps-2"></p>
          </div>
          <div class="form-group mb-3">
              <label>Username:</label>
              <p id="update_username" class="form-control-plaintext ps-2"></p>
          </div>
          <hr/>
          <div class="form-group mb-3">
              <label for="update_department_id">Department</label>
              <select name="department_id" id="update_department_id" class="form-select" required>
                  <option value="">Select Department</option>
              </select>
          </div>
          <div class="form-group mb-3">
              <label for="update_division_id">Division</label>
              <select name="division_id" id="update_division_id" class="form-select" required>
                  <option value="">Select Division</option>
              </select>
          </div>
          <div class="form-group mb-3">
              <label for="update_section_id">Section</label>
              <select name="section_id" id="update_section_id" class="form-select" required>
                  <option value="">Select Section</option>
              </select>
          </div>
          <div class="form-group mb-3">
              <label for="update_role">Role</label>
              <select id="update_role" name="role" class="form-select" required>
                  <option value="ADMINISTRATOR">ADMINISTRATOR</option>
                  <option value="HR">HR</option>
                  <option value="GA">GA</option>
                  <option value="GUEST">GUEST</option>
              </select>
          </div>
          <div class="form-group">
              <label for="update_status">Status</label>
              <select id="update_status" name="status" class="form-select" required>
                  <option value="ACTIVE">ACTIVE</option>
                  <option value="INACTIVE">INACTIVE</option>
              </select>
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
    const usersTable = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('users_maintenance/getUsers') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'user_id' },
            { data: 'username' },
            { data: 'firstname' },
            { data: 'lastname' },
            { data: 'status' },
            { data: 'role' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    // --- Dependent Dropdown Logic ---
    function loadDepartments(callback) {
        $.getJSON('<?= base_url('division_maintenance/getDepartmentsForDropdown') ?>', function (res) {
            if (res.status === 'success') {
                const selects = $('#add_department_id, #update_department_id');
                selects.empty().append('<option value="">Select Department</option>');
                res.data.forEach(function(dept) {
                    selects.append(`<option value="${dept.department_id}">${dept.department_name}</option>`);
                });
                if (callback) callback();
            }
        });
    }

    function loadDivisions(departmentId, divisionSelectId, selectedDivisionId = null) {
        const divisionSelect = $(`#${divisionSelectId}`);
        const sectionSelect = $(`#${divisionSelectId.replace('division', 'section')}`);
        divisionSelect.empty().append('<option value="">Loading...</option>');
        sectionSelect.empty().append('<option value="">Select a Division first</option>');
        if (departmentId) {
            $.getJSON(`<?= base_url('section_maintenance/getDivisionsByDepartment/') ?>${departmentId}`, function (res) {
                divisionSelect.empty().append('<option value="">Select Division</option>');
                if (res.status === 'success') {
                    res.data.forEach(function(div) {
                        divisionSelect.append(`<option value="${div.division_id}">${div.division_name}</option>`);
                    });
                }
                if (selectedDivisionId) {
                    divisionSelect.val(selectedDivisionId).trigger('change');
                }
            });
        } else {
            divisionSelect.empty().append('<option value="">Select a Department first</option>');
        }
    }

    function loadSections(divisionId, sectionSelectId, selectedSectionId = null) {
        const sectionSelect = $(`#${sectionSelectId}`);
        sectionSelect.empty().append('<option value="">Loading...</option>');
        if (divisionId) {
            $.getJSON(`<?= base_url('dri_maintenance/getSectionsByDivision/') ?>${divisionId}`, function (res) {
                sectionSelect.empty().append('<option value="">Select Section</option>');
                if (res.status === 'success') {
                    res.data.forEach(function(sec) {
                        sectionSelect.append(`<option value="${sec.section_id}">${sec.section_name}</option>`);
                    });
                }
                if (selectedSectionId) {
                    sectionSelect.val(selectedSectionId);
                }
            });
        } else {
            sectionSelect.empty().append('<option value="">Select a Division first</option>');
        }
    }

    loadDepartments();

    // Event listeners for dropdowns
    $('#add_department_id').on('change', function() { loadDivisions($(this).val(), 'add_division_id'); });
    $('#add_division_id').on('change', function() { loadSections($(this).val(), 'add_section_id'); });
    $('#update_department_id').on('change', function() { loadDivisions($(this).val(), 'update_division_id'); });
    $('#update_division_id').on('change', function() { loadSections($(this).val(), 'update_section_id'); });

    // Add User Form Submission
    $('#addUserForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Adding...');
        $.post('<?= base_url('users_maintenance/add') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#usersAddModal').modal('hide');
                $('#addUserForm')[0].reset();
                usersTable.ajax.reload();
                Swal.fire('Added!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Add failed.', 'error');
            }
        }, 'json').fail(function() {
            Swal.fire('Error!', 'An unexpected server error occurred.', 'error');
        }).always(() => $btn.prop('disabled', false).html('Add Account'));
    });

    // Update User Form Submission
    $('#updateUserForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Saving...');
        $.post('<?= base_url('users_maintenance/update') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#usersUpdateModal').modal('hide');
                usersTable.ajax.reload();
                Swal.fire('Updated!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Update failed.', 'error');
            }
        }, 'json').fail(function() {
            Swal.fire('Error!', 'An unexpected server error occurred.', 'error');
        }).always(() => $btn.prop('disabled', false).html('Save Changes'));
    });

    // MOVED: The edit and delete functions are now inside document.ready
    // They are attached to the `window` object so the inline onclick="..." attribute can still find them.

    window.editUser = function(userId) {
        $.getJSON(`<?= base_url('users_maintenance/details/') ?>${userId}`, function (res) {
            if (res.status === 'success') {
                const user = res.data;
                $('#updateUserForm')[0].reset(); 

                $('#update_user_id').val(user.user_id);
                $('#update_employee_id').text(user.employee_id);
                $('#update_username').text(user.username);
                $('#update_role').val(user.role);
                $('#update_status').val(user.status);
                
                // This call will now work correctly
                loadDepartments(function() {
                    $('#update_department_id').val(user.department_id);
                    loadDivisions(user.department_id, 'update_division_id', user.division_id);
                    loadSections(user.division_id, 'update_section_id', user.section_id);
                });

                $('#usersUpdateModal').modal('show');
            } else {
                Swal.fire('Error!', res.message || 'Unable to fetch user.', 'error');
            }
        });
    }

    window.deleteUser = function(userId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This account will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`<?= base_url('users_maintenance/delete/') ?>${userId}`, function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Deleted!', res.message, 'success');
                        $('#usersTable').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Error!', res.message || 'Deletion failed.', 'error');
                    }
                }, 'json');
            }
        });
    }
});
</script>
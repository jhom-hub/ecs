<div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">DRI Management</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDriModal">
                        Add DRI
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="drisTable" class="table table-sm table-striped table-bordered text-dark w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Area</th>
                                    <th>Section</th>
                                    <th>Division</th>
                                    <th>Department</th>
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

<div class="modal fade" id="addDriModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="addDriForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add DRI</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="form-group mb-3">
              <label for="add_area_id">Area</label>
              <select name="area_id" id="add_area_id" class="form-select" required>
                  <option value="">Select Area</option>
              </select>
          </div>
          <div class="form-group">
              <label for="add_user_id">Full Name (User)</label>
              <select name="user_id" id="add_user_id" class="form-select" required>
                  <option value="">Select User</option>
              </select>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add DRI</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="updateDriModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="updateDriForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update DRI</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="update_dri_id" name="dri_id">
           <div class="form-group mb-3">
              <label for="update_area_id">Area</label>
              <select name="area_id" id="update_area_id" class="form-select" required>
                  <option value="">Select Area</option>
              </select>
          </div>
          <div class="form-group">
              <label for="update_user_id">Full Name (User)</label>
              <select name="user_id" id="update_user_id" class="form-select" required>
                  <option value="">Select User</option>
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
    const drisTable = $('#drisTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('dri_maintenance/getDris') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'dri_id' },
            { data: 'fullname' },
            { data: 'area_name' },
            { data: 'section_name' },
            { data: 'division_name' },
            { data: 'department_name' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    // --- NEW Dropdown Logic ---

    // Function to load all areas into dropdowns
    function loadAreas() {
        $.getJSON('<?= base_url('dri_maintenance/getAreasForDropdown') ?>', function (res) {
            if (res.status === 'success') {
                const selects = $('#add_area_id, #update_area_id');
                selects.empty().append('<option value="">Select Area</option>');
                res.data.forEach(function(area) {
                    selects.append(`<option value="${area.area_id}">${area.area_name}</option>`);
                });
            }
        });
    }
    
    // Function to load all users into dropdowns
    function loadUsers() {
        $.getJSON('<?= base_url('dri_maintenance/getUsersForDropdown') ?>', function (res) {
            if (res.status === 'success') {
                const selects = $('#add_user_id, #update_user_id');
                selects.empty().append('<option value="">Select User</option>');
                res.data.forEach(function(user) {
                    selects.append(`<option value="${user.user_id}">${user.fullname}</option>`);
                });
            }
        });
    }

    // Initial load of new dropdowns
    loadAreas();
    loadUsers();

    // --- CRUD Logic ---

    $('#addDriForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Adding...');
        $.post('<?= base_url('dri_maintenance/addDri') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#addDriModal').modal('hide');
                $('#addDriForm')[0].reset();
                drisTable.ajax.reload();
                Swal.fire('Added!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Add failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Add DRI'));
    });

    $('#updateDriForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');
        $.post('<?= base_url('dri_maintenance/updateDri') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#updateDriModal').modal('hide');
                drisTable.ajax.reload();
                Swal.fire('Updated!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Update failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Save Changes'));
    });
});

// REVAMPED: Edit function now sets the area and user dropdowns
function editDri(driId) {
    $.getJSON(`<?= base_url('dri_maintenance/details/') ?>${driId}`, function (res) {
        if (res.status === 'success') {
            const dri = res.data;
            $('#update_dri_id').val(dri.dri_id);
            
            // Set the dropdown values from the fetched data
            $('#update_area_id').val(dri.area_id);
            $('#update_user_id').val(dri.user_id);

            $('#updateDriModal').modal('show');
        } else {
            Swal.fire('Error!', res.message || 'Unable to fetch DRI details.', 'error');
        }
    });
}

function deleteDri(driId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This DRI will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(`<?= base_url('dri_maintenance/deleteDri/') ?>${driId}`, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Deleted!', res.message, 'success');
                    $('#drisTable').DataTable().ajax.reload();
                } else {
                    Swal.fire('Error!', res.message || 'Deletion failed.', 'error');
                }
            }, 'json');
        }
    });
}
</script>
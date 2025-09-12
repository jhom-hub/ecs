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
                                    <th>Department</th>
                                    <th>Area</th>
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
          <div class="form-group mb-3">
              <label for="add_department_id">Department</label>
              <select name="department_id" id="add_department_id" class="form-select" required>
                  <option value="">Select Department</option>
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
          <div class="form-group mb-3">
              <label for="update_department_id">Department</label>
              <select name="department_id" id="update_department_id" class="form-select" required>
                  <option value="">Select Department</option>
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
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

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

    function loadDepartments() {
        $.getJSON('<?= base_url('dri_maintenance/getDepartmentsForDropdown') ?>', function (res) {
            if (res.status === 'success') {
                const selects = $('#add_department_id, #update_department_id');
                selects.empty().append('<option value="">Select Department</option>');
                res.data.forEach(function(dept) {
                    selects.append(`<option value="${dept.department_id}">${dept.department_name}</option>`);
                });
            }
        });
    }

    loadAreas();
    loadDepartments();

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

function editDri(driId) {
    $.getJSON(`<?= base_url('dri_maintenance/details/') ?>${driId}`, function (res) {
        if (res.status === 'success') {
            const dri = res.data;
            $('#update_dri_id').val(dri.dri_id);
            $('#update_area_id').val(dri.area_id);

            if ($('#update_department_id option[value="' + dri.department_id + '"]').length > 0) {
                $('#update_department_id').val(dri.department_id);
            } else {
                if (dri.department_id && dri.department_name) {
                    const newOption = new Option(dri.department_name + " (Current)", dri.department_id, true, true);
                    $('#update_department_id').append(newOption);
                } else {
                    $('#update_department_id').val('');
                }
            }
            
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
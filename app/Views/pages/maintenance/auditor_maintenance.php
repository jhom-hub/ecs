<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Auditor Management</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAuditorModal">
                        Add Assignment
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="auditorTable" class="table table-sm table-striped table-bordered text-dark w-100">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Full Name</th>
                                    <th>Assigned Areas</th>
                                    <th>First Assigned On</th>
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

<div class="modal fade" id="addAuditorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="addAuditorForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Auditor Assignments</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="form-group mb-3">
              <label for="add_user_id">Full Name (User)</label>
              <select name="user_id" id="add_user_id" class="form-select" required>
                  <option value="">Select User...</option>
              </select>
          </div>
          <div class="form-group">
              <label for="add_area_id">Areas</label>
              <select name="area_id[]" id="add_area_id" class="form-select" multiple required>
              </select>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add Assignments</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="updateAuditorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="updateAuditorForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Auditor Assignments</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="update_user_id" name="user_id">
          
          <div class="form-group mb-3">
              <label>Full Name (User)</label>
              <p id="update_user_name" class="form-control-static fw-bold"></p>
          </div>
          <div class="form-group">
              <label for="update_area_id">Areas</label>
              <select name="area_id[]" id="update_area_id" class="form-select" multiple required>
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
    const auditorTable = $('#auditorTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('auditor_maintenance/getAuditors') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'user_id' },
            { data: 'fullname' },
            { data: 'area_name' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    function initSelect2(selector) {
        $(selector).select2({
            theme: 'bootstrap-5',
            dropdownParent: $(selector).closest('.modal')
        });
    }

    initSelect2('#add_user_id');
    initSelect2('#add_area_id');
    initSelect2('#update_area_id');

    function populateDropdowns() {
        $.getJSON('<?= base_url('auditor_maintenance/getAreas') ?>', function (areas) {
            const areaSelects = $('#add_area_id, #update_area_id');
            areaSelects.empty();
            areas.forEach(function(area) {
                areaSelects.append(new Option(area.area_name, area.area_id, false, false));
            });
            areaSelects.trigger('change');
        });

        $.getJSON('<?= base_url('auditor_maintenance/getUsers') ?>', function (users) {
            const userSelect = $('#add_user_id');
            userSelect.find('option:not(:first)').remove();
            users.forEach(function(user) {
                userSelect.append(new Option(user.fullname, user.user_id, false, false));
            });
            userSelect.trigger('change');
        });
    }
    populateDropdowns();

    $('#addAuditorForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '<?= base_url('auditor_maintenance/create') ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                Swal.fire('Success!', response.message, 'success');
                $('#addAuditorModal').modal('hide');
                auditorTable.ajax.reload();
            },
            error: function (xhr) {
                const error = JSON.parse(xhr.responseText);
                Swal.fire('Error!', error.message, 'error');
            }
        });
    });
    
    window.editAuditor = function (userId) {
        $.getJSON('<?= base_url('auditor_maintenance/getAssignmentsForUser/') ?>' + userId, function (response) {
            if (response.status === 'success') {
                $('#update_user_id').val(response.user.user_id);
                $('#update_user_name').text(response.user.fullname);
                $('#update_area_id').val(response.area_ids).trigger('change');
                $('#updateAuditorModal').modal('show');
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        });
    };
    
    $('#updateAuditorForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '<?= base_url('auditor_maintenance/update') ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                Swal.fire('Success!', response.message, 'success');
                $('#updateAuditorModal').modal('hide');
                auditorTable.ajax.reload();
            },
            error: function (xhr) {
                const error = JSON.parse(xhr.responseText);
                Swal.fire('Error!', error.message, 'error');
            }
        });
    });

    window.deleteAuditor = function (userId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete ALL area assignments for this user!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete all!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url('auditor_maintenance/delete/') ?>' + userId,
                    type: 'POST',
                    dataType: 'json',
                    success: function (response) {
                        Swal.fire('Deleted!', response.message, 'success');
                        auditorTable.ajax.reload();
                    },
                    error: function (xhr) {
                        const error = JSON.parse(xhr.responseText);
                        Swal.fire('Error!', error.message, 'error');
                    }
                });
            }
        });
    };

    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $(this).find('select').val(null).trigger('change');
    });
});
</script>
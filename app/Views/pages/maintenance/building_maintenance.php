<div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Building Management</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBuildingModal">
                        Add Building
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="buildingsTable" class="table table-sm table-striped table-bordered text-dark w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Building Name</th>
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
<div class="modal fade" id="addBuildingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="addBuildingForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Building</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="form-group">
              <label for="building_name">Building Name</label>
              <input type="text" name="building_name" class="form-control" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add Building</button>
      </div>
    </form>
  </div>
</div>

<!-- UPDATE MODAL -->
<div class="modal fade" id="updateBuildingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="updateBuildingForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Building</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="update_building_id" name="building_id">
          <div class="form-group">
              <label for="update_building_name">Building Name</label>
              <input type="text" id="update_building_name" name="building_name" class="form-control" required>
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
    const buildingsTable = $('#buildingsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('building_maintenance/getBuildings') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'building_id' },
            { data: 'building_name' },
            { data: 'created_at' },
            { data: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    // Add Building
    $('#addBuildingForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Adding...');
        $.post('<?= base_url('building_maintenance/addBuilding') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#addBuildingModal').modal('hide');
                $('#addBuildingForm')[0].reset();
                buildingsTable.ajax.reload();
                Swal.fire('Added!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Add failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Add Building'));
    });

    // Update Building
    $('#updateBuildingForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');
        $.post('<?= base_url('building_maintenance/updateBuilding') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#updateBuildingModal').modal('hide');
                buildingsTable.ajax.reload();
                Swal.fire('Updated!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Update failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Save Changes'));
    });
});

// Trigger Edit Modal
function editBuilding(buildingId) {
    $.getJSON(`<?= base_url('building_maintenance/details/') ?>${buildingId}`, function (res) {
        if (res.status === 'success') {
            const bld = res.data;
            $('#update_building_id').val(bld.building_id);
            $('#update_building_name').val(bld.building_name);
            $('#updateBuildingModal').modal('show');
        } else {
            Swal.fire('Error!', res.message || 'Unable to fetch building details.', 'error');
        }
    });
}

// Delete Building with SweetAlert
function deleteBuilding(buildingId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This building will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#e3342f'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(`<?= base_url('building_maintenance/deleteBuilding/') ?>${buildingId}`, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Deleted!', res.message, 'success');
                    $('#buildingsTable').DataTable().ajax.reload();
                } else {
                    Swal.fire('Error!', res.message || 'Deletion failed.', 'error');
                }
            }, 'json');
        }
    });
}
</script>
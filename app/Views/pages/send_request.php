<div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Existing Requests</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="requestItemsTable" class="table table-sm table-striped table-bordered text-dark w-100">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Control #</th>
                                    <th>Status</th>
                                    <th>Timestamp</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Send New Item Request</h4>
                </div>
                <div class="card-body">
                    <form id="sendRequestForm" class="form-group w-100">
                        <div class="mb-3">
                            <label for="send_building_id" class="form-label">Building</label>
                            <select name="building_id" id="send_building_id" class="form-select" required></select>
                        </div>
                        <div class="mb-3">
                            <label for="send_area_id" class="form-label">Area</label>
                            <select name="area_id" id="send_area_id" class="form-select" required></select>
                        </div>
                        <div class="mb-3">
                            <label for="send_item_name" class="form-label">Item Name (Request)</label>
                            <input type="text" name="item_name" id="send_item_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="send_description" class="form-label">Description</label>
                            <textarea name="description" id="send_description" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3 w-100">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="updateRequestItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="updateRequestItemForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Item Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="update_request_id" name="request_id">
          <div class="row">
              <div class="col-md-6">
                  <h5>Location</h5>
                  <div class="form-group mb-3">
                      <label>Building</label>
                      <select name="building_id" id="update_building_id" class="form-control" required></select>
                  </div>
                  <div class="form-group mb-3">
                      <label>Area</label>
                      <select name="area_id" id="update_area_id" class="form-control" required></select>
                  </div>
                  <div class="form-group mb-3">
                      <label>Item Name (Request)</label>
                      <input type="text" name="item_name" id="update_item_name" class="form-control" required>
                  </div>
                   <div class="form-group mb-3">
                      <label>Description</label>
                      <textarea name="description" id="update_description" class="form-control"></textarea>
                  </div>
              </div>
              <div class="col-md-6">
                  <h5>Approval Details</h5>
                   <div class="form-group mb-3">
                      <label>Status</label>
                      <select name="status" id="update_status" class="form-select" required>
                          <option value="0">Pending</option>
                          <option value="1">Approved</option>
                      </select>
                  </div>
                  <div class="form-group">
                      <label>Remarks</label>
                      <textarea name="remarks" id="update_remarks" class="form-control" rows="5"></textarea>
                  </div>
              </div>
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
    const requestItemsTable = $('#requestItemsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('send_request/getRequestItems') ?>',
            type: 'POST'
        },
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
        columns: [
            { data: 'item_name' },
            { data: 'control' },
            { 
                data: 'status',
                render: function(data) {
                    if (data == 0) {
                        return '<span class="badge bg-warning text-dark">Pending</span>';
                    } else if (data == 1) {
                        return '<span class="badge bg-success">Approved</span>';
                    } else {
                        return '<span class="badge bg-danger">Rejected</span>';
                    }
                }
            },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        columnDefs:[
            {
                className: 'dtr-control',
                orderable: false,
                targets: 0
            },
        ],
        order: [[0, 'desc']]
    });

    function loadBuildings(callback, selector = '#send_building_id, #update_building_id') {
        $.getJSON('<?= base_url('area_maintenance/getBuildingsForDropdown') ?>', function (res) {
            const selects = $(selector);
            selects.empty().append('<option value="">Select Building</option>');
            if (res.status === 'success') {
                res.data.forEach(bld => selects.append(`<option value="${bld.building_id}">${bld.building_name}</option>`));
            }
            if (callback) callback();
        });
    }

    function loadAreas(buildingId, areaSelectId, selectedAreaId = null) {
        const areaSelect = $(`#${areaSelectId}`);
        areaSelect.empty().append('<option value="">Loading...</option>');
        if (buildingId) {
            $.getJSON(`<?= base_url('item_maintenance/getAreasByBuilding/') ?>${buildingId}`, function (res) {
                areaSelect.empty().append('<option value="">Select Area</option>');
                if (res.status === 'success') {
                    res.data.forEach(area => areaSelect.append(`<option value="${area.area_id}">${area.area_name}</option>`));
                }
                if (selectedAreaId) areaSelect.val(selectedAreaId);
            });
        } else {
            areaSelect.empty().append('<option value="">Select a Building first</option>');
        }
    }

    loadBuildings(null, '#send_building_id');

    $('#send_building_id').on('change', function() { loadAreas($(this).val(), 'send_area_id'); });
    $('#update_building_id').on('change', function() { loadAreas($(this).val(), 'update_area_id'); });

    $('#sendRequestForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Submitting...');
        
        $.post('<?= base_url('send_request/addRequestItem') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#sendRequestForm')[0].reset();
                $('#send_area_id').empty().append('<option value="">Select Area</option>');
                requestItemsTable.ajax.reload();
                Swal.fire('Success!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Request failed.', 'error');
            }
        }, 'json').fail(xhr => Swal.fire('Error!', xhr.responseJSON ? xhr.responseJSON.message : 'An unknown error occurred.', 'error'))
          .always(() => $btn.prop('disabled', false).text('Submit Request'));
    });
    
    $('#updateRequestItemForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');
        
        $.post('<?= base_url('send_request/updateRequestItem') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#updateRequestItemModal').modal('hide');
                requestItemsTable.ajax.reload();
                Swal.fire('Success!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Request failed.', 'error');
            }
        }, 'json').fail(xhr => Swal.fire('Error!', xhr.responseJSON ? xhr.responseJSON.message : 'An unknown error occurred.', 'error'))
          .always(() => $btn.prop('disabled', false).text('Save Changes'));
    });

    // **FIX:** Moved these functions INSIDE the document.ready block
    window.editRequest = function(id) {
        $.getJSON(`<?= base_url('send_request/getRequestItemDetails/') ?>${id}`, function (res) {
            if (res.status === 'success') {
                const data = res.data;
                $('#update_request_id').val(data.request_id);
                $('#update_item_name').val(data.item_name);
                $('#update_status').val(data.status);
                $('#update_description').val(data.description);
                $('#update_remarks').val(data.remarks);
                
                // Use a callback to ensure buildings load before areas are set
                loadBuildings(() => {
                    $('#update_building_id').val(data.building_id);
                    loadAreas(data.building_id, 'update_area_id', data.area_id);
                }, '#update_building_id');

                $('#updateRequestItemModal').modal('show');
            } else {
                Swal.fire('Error!', res.message, 'error');
            }
        });
    }

    window.deleteRequest = function(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This request will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`<?= base_url('send_request/deleteRequestItem/') ?>${id}`, function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Deleted!', res.message, 'success');
                        $('#requestItemsTable').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Error!', res.message, 'error');
                    }
                }, 'json');
            }
        });
    }
});
</script>
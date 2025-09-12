<div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Section Management</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                        Add Section
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="sectionsTable" class="table table-sm table-striped table-bordered text-dark w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Section Name</th>
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

<div class="modal fade" id="addSectionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="addSectionForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Section</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="form-group mb-3">
              <label for="add_department_id">Department</label>
              <select id="add_department_id" class="form-control" required>
                  <option value="">Select Department</option>
              </select>
          </div>
          <div class="form-group mb-3">
              <label for="add_division_id">Division</label>
              <select name="division_id" id="add_division_id" class="form-control" required>
                  <option value="">Select Division</option>
              </select>
          </div>
          <div class="form-group">
              <label for="section_name">Section Name</label>
              <input type="text" name="section_name" class="form-control" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add Section</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="updateSectionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="updateSectionForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Section</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="update_section_id" name="section_id">
          <div class="form-group mb-3">
              <label for="update_department_id">Department</label>
              <select id="update_department_id" class="form-control" required>
                  <option value="">Select Department</option>
              </select>
          </div>
          <div class="form-group mb-3">
              <label for="update_division_id">Division</label>
              <select name="division_id" id="update_division_id" class="form-control" required>
                  <option value="">Select Division</option>
              </select>
          </div>
          <div class="form-group">
              <label for="update_section_name">Section Name</label>
              <input type="text" id="update_section_name" name="section_name" class="form-control" required>
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
    const sectionsTable = $('#sectionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('section_maintenance/getSections') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'section_id' },
            { data: 'section_name' },
            { data: 'division_name' },
            { data: 'department_name' },
            { data: 'created_at' },
            { data: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    // Function to load departments into select dropdowns
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

    // Function to load divisions based on department
    function loadDivisions(departmentId, divisionSelectId, selectedDivisionId = null) {
        const divisionSelect = $(`#${divisionSelectId}`);
        divisionSelect.empty().append('<option value="">Loading...</option>'); // Show loading state
        if (departmentId) {
            $.getJSON(`<?= base_url('section_maintenance/getDivisionsByDepartment/') ?>${departmentId}`, function (res) {
                if (res.status === 'success') {
                    divisionSelect.empty().append('<option value="">Select Division</option>');
                    res.data.forEach(function(div) {
                        divisionSelect.append(`<option value="${div.division_id}">${div.division_name}</option>`);
                    });
                    if (selectedDivisionId) {
                        divisionSelect.val(selectedDivisionId);
                    }
                }
            });
        } else {
            divisionSelect.empty().append('<option value="">Select a Department first</option>');
        }
    }

    loadDepartments();

    // Event listeners for department dropdowns
    $('#add_department_id').on('change', function() {
        loadDivisions($(this).val(), 'add_division_id');
    });
    $('#update_department_id').on('change', function() {
        loadDivisions($(this).val(), 'update_division_id');
    });

    // Add Section
    $('#addSectionForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Adding...');
        $.post('<?= base_url('section_maintenance/addSection') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#addSectionModal').modal('hide');
                $('#addSectionForm')[0].reset();
                sectionsTable.ajax.reload();
                Swal.fire('Added!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Add failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Add Section'));
    });

    // Update Section
    $('#updateSectionForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');
        $.post('<?= base_url('section_maintenance/updateSection') ?>', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                $('#updateSectionModal').modal('hide');
                sectionsTable.ajax.reload();
                Swal.fire('Updated!', res.message, 'success');
            } else {
                Swal.fire('Error!', res.message || 'Update failed.', 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false).text('Save Changes'));
    });

    // **FIX:** Moved these functions INSIDE the document.ready block.
    // They are attached to the `window` object to be accessible by the `onclick` attribute in the HTML buttons.
    window.editSection = function(sectionId) {
        $.getJSON(`<?= base_url('section_maintenance/details/') ?>${sectionId}`, function (res) {
            if (res.status === 'success') {
                const sec = res.data;
                $('#update_section_id').val(sec.section_id);
                $('#update_section_name').val(sec.section_name);
                
                // **IMPROVED LOGIC:** Use a callback to ensure departments load before divisions are populated and selected.
                loadDepartments(function() {
                    $('#update_department_id').val(sec.department_id);
                    loadDivisions(sec.department_id, 'update_division_id', sec.division_id);
                });

                $('#updateSectionModal').modal('show');
            } else {
                Swal.fire('Error!', res.message || 'Unable to fetch section details.', 'error');
            }
        });
    }

    window.deleteSection = function(sectionId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This section will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#e3342f'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`<?= base_url('section_maintenance/deleteSection/') ?>${sectionId}`, function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Deleted!', res.message, 'success');
                        $('#sectionsTable').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Error!', res.message || 'Deletion failed.', 'error');
                    }
                }, 'json');
            }
        });
    }
});
</script>
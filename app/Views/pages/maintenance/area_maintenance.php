<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Area Management</h4>
                <button class="btn btn-primary" id="addAreaBtn">
                    Add Area
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="areasTable" class="table table-sm table-striped table-bordered text-dark w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Area Name</th>
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

<!-- Re-purposed Add/Update Modal -->
<div class="modal fade" id="areaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <form id="areaForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Area</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="area_id" name="area_id">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="building_id">Building</label>
                            <select name="building_id" id="building_id" class="form-select" required></select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="area_name">Area Name</label>
                            <input type="text" name="area_name" id="area_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="x_coords">X Coordinate</label>
                            <input type="number" step="any" name="x_coords" id="x_coords" class="form-control">
                        </div>
                        <div class="form-group mb-3">
                            <label for="y_coords">Y Coordinate</label>
                            <input type="number" step="any" name="y_coords" id="y_coords" class="form-control">
                        </div>
                    </div>
                </div>
                <hr>
                <h6>Items for this Area</h6>
                <div id="itemsContainer">
                    <!-- Item rows will be dynamically inserted here -->
                </div>
                <button type="button" class="btn btn-success btn-sm mt-2" id="addItemBtn">
                    <i class='bx bx-plus'></i> Add Item
                </button>
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
        const areasTable = $('#areasTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: '<?= base_url('area_maintenance/getAreas') ?>', type: 'POST' },
            columns: [
                { data: 'area_id' }, { data: 'area_name' }, { data: 'building_name' },
                { data: 'created_at' }, { data: 'updated_at' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']]
        });

        function loadBuildings(selectedId = null) {
            $.getJSON('<?= base_url('area_maintenance/getBuildingsForDropdown') ?>', function (res) {
                const selects = $('#building_id');
                selects.empty().append('<option value="">Select Building</option>');
                if (res.status === 'success') {
                    res.data.forEach(bld => selects.append(`<option value="${bld.building_id}">${bld.building_name}</option>`));
                }
                if(selectedId) selects.val(selectedId);
            });
        }
        
        loadBuildings();

        function addItemRow(item = { item_name: '' }) {
            const itemRow = `
                <div class="row item-row mb-2">
                    <div class="col-9">
                        <input type="text" name="item_name[]" class="form-control form-control-sm" placeholder="Item Name" value="${item.item_name}" required>
                    </div>
                    <div class="col-3">
                        <button type="button" class="btn btn-danger btn-sm remove-item-btn w-100">Remove</button>
                    </div>
                </div>
            `;
            $('#itemsContainer').append(itemRow);
        }

        $('#addItemBtn').on('click', () => addItemRow());
        $('#itemsContainer').on('click', '.remove-item-btn', function() {
            $(this).closest('.item-row').remove();
        });

        $('#addAreaBtn').on('click', function() {
            $('#areaForm')[0].reset();
            $('#area_id').val('');
            $('#modalTitle').text('Add Area');
            $('#itemsContainer').empty();
            addItemRow();
            $('#areaModal').modal('show');
        });

        $('#areaForm').submit(function (e) {
            e.preventDefault();
            const url = $('#area_id').val() ? '<?= base_url('area_maintenance/updateArea') ?>' : '<?= base_url('area_maintenance/addArea') ?>';
            const $btn = $(this).find('button[type="submit"]');
            $btn.prop('disabled', true).text('Saving...');
            
            $.post(url, $(this).serialize(), function (res) {
                if (res.status === 'success') {
                    $('#areaModal').modal('hide');
                    areasTable.ajax.reload();
                    Swal.fire('Success!', res.message, 'success');
                } else {
                    Swal.fire('Error!', res.message || 'Request failed.', 'error');
                }
            }, 'json').always(() => $btn.prop('disabled', false).text('Save Changes'));
        });
    });

    function editArea(areaId) {
        $.getJSON(`<?= base_url('area_maintenance/details/') ?>${areaId}`, function (res) {
            if (res.status === 'success') {
                const area = res.data;
                const form = $('#areaForm');
                form[0].reset();
                $('#modalTitle').text('Update Area');
                
                $('#area_id').val(area.area_id);
                $('#area_name').val(area.area_name);
                $('#building_id').val(area.building_id);
                $('#x_coords').val(area.x_coords);
                $('#y_coords').val(area.y_coords);

                const itemsContainer = $('#itemsContainer');
                itemsContainer.empty();
                if (area.items && area.items.length > 0) {
                    area.items.forEach(item => {
                        const itemRow = `
                            <div class="row item-row mb-2">
                                <div class="col-9"><input type="text" name="item_name[]" class="form-control form-control-sm" value="${item.item_name}" required></div>
                                <div class="col-3"><button type="button" class="btn btn-danger btn-sm remove-item-btn w-100">Remove</button></div>
                            </div>`;
                        itemsContainer.append(itemRow);
                    });
                } else {
                     const emptyRow = `
                        <div class="row item-row mb-2">
                            <div class="col-9"><input type="text" name="item_name[]" class="form-control form-control-sm" placeholder="Item Name" required></div>
                            <div class="col-3"><button type="button" class="btn btn-danger btn-sm remove-item-btn w-100">Remove</button></div>
                        </div>`;
                    itemsContainer.append(emptyRow);
                }
                
                $('#areaModal').modal('show');
            } else {
                Swal.fire('Error!', res.message || 'Unable to fetch area details.', 'error');
            }
        });
    }

    function deleteArea(areaId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This area and all its items will be permanently deleted.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`<?= base_url('area_maintenance/deleteArea/') ?>${areaId}`, function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Deleted!', res.message, 'success');
                        $('#areasTable').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Error!', res.message || 'Deletion failed.', 'error');
                    }
                }, 'json');
            }
        });
    }
</script>


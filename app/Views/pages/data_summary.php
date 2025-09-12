<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Data Summary Filtering</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="building_filter" class="form-label">Building</label>
                    <select id="building_filter" class="form-select">
                        <option value="">All Buildings</option>
                        <?php foreach($buildings as $bld): ?>
                            <option value="<?= $bld['building_id'] ?>"><?= esc($bld['building_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="area_filter" class="form-label">Area</label>
                    <select id="area_filter" class="form-select" disabled>
                        <option value="">Select Building First</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="department_filter" class="form-label">Department</label>
                    <select id="department_filter" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept['department_id'] ?>"><?= esc($dept['department_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="col-md-3 mb-3">
                    <label for="dri_filter" class="form-label">DRI</label>
                    <select id="dri_filter" class="form-select">
                        <option value="">All DRIs</option>
                         <?php foreach($dris as $dri): ?>
                            <option value="<?= $dri['dri_id'] ?>"><?= esc($dri['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="auditor_filter" class="form-label">Auditor</label>
                    <select id="auditor_filter" class="form-select">
                        <option value="">All Auditors</option>
                         <?php foreach($dris as $dri): // Auditors are also from the 'dris' table ?>
                            <option value="<?= $dri['dri_id'] ?>"><?= esc($dri['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="status_filter" class="form-label">Status</label>
                    <select id="status_filter" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="0">NG</option>
                        <option value="1">For Verification</option>
                        <option value="2">OK</option>
                        <option value="3">HOLD</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="start_date_filter" class="form-label">Start Date</label>
                    <input type="date" id="start_date_filter" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="end_date_filter" class="form-label">End Date</label>
                    <input type="date" id="end_date_filter" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-end">
                    <button id="resetBtn" class="btn btn-secondary">Reset</button>
                    <button id="filterBtn" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="summaryTable" class="table table-sm table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Building</th>
                            <th>Area</th>
                            <th>Item</th>
                            <th>DRI</th>
                            <th>Auditor</th>
                            <th>Department</th>
                            <th>Finding</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Note: The 'baseUrl' constant is defined in your main.php layout
    const summaryTable = $('#summaryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: baseUrl + 'summary/getSummaryData',
            type: 'POST',
            data: function(d) {
                d.building_id = $('#building_filter').val();
                d.area_id = $('#area_filter').val();
                d.department_id = $('#department_filter').val();
                d.dri_id = $('#dri_filter').val();
                d.auditor_id = $('#auditor_filter').val(); // New filter
                d.status = $('#status_filter').val();
                d.start_date = $('#start_date_filter').val();
                d.end_date = $('#end_date_filter').val();
            }
        },
        columns: [
            { data: 'data_id' },
            { data: 'building_name' },
            { data: 'area_name' },
            { data: 'item_name' },
            { data: 'dri_name' },
            { data: 'auditor_name' }, // New column
            { data: 'department_name' },
            { data: 'findings_name' },
            { data: 'status' },
            { data: 'submitted_date' }
        ],
        order: [[0, 'desc']]
    });

    $('#filterBtn').on('click', function() {
        summaryTable.ajax.reload();
    });

    $('#resetBtn').on('click', function() {
        // Reset all filter fields
        $('#building_filter, #area_filter, #department_filter, #dri_filter, #auditor_filter, #status_filter').val('');
        $('#start_date_filter, #end_date_filter').val('');
        
        // Disable area filter and reset its options
        $('#area_filter').prop('disabled', true).empty().append('<option value="">Select Building First</option>');
        
        // Redraw the table with no filters
        summaryTable.ajax.reload();
    });

    $('#building_filter').on('change', function() {
        const buildingId = $(this).val();
        const areaSelect = $('#area_filter');
        
        if (buildingId) {
            areaSelect.prop('disabled', false).empty().append('<option value="">Loading...</option>');
            $.getJSON(baseUrl + 'item_maintenance/getAreasByBuilding/' + buildingId, res => {
                areaSelect.empty().append('<option value="">All Areas</option>');
                if (res.status === 'success') {
                    res.data.forEach(area => areaSelect.append(`<option value="${area.area_id}">${area.area_name}</option>`));
                }
            });
        } else {
            areaSelect.prop('disabled', true).empty().append('<option value="">Select Building First</option>');
        }
    });

    // The department filter does not have dependent dropdowns in this version,
    // so its 'change' event listener is not needed unless you want to filter
    // DRIs by department, which would require an additional AJAX call.
});
</script>
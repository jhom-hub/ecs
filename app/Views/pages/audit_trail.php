<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><?= $pageTitle ?? 'Audit Trail' ?></h4>
                <a href="<?= base_url('audit_trail/download-audit-trail') ?>" class="btn btn-primary">
                    <i class="mdi mdi-account-plus"></i> Download
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="auditTable" class="table table-sm table-striped table-bordered text-dark w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Created_At</th>
                                <th>Updated_At</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    const auditTable = $('#auditTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('audit_trail/getTrails') ?>',
            type: 'POST'
        },
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'action' },
            { data: 'ip_address' },
            { data: 'created_at' },
            { data: 'updated_at' }
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
});
</script>

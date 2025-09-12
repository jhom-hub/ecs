<div class="row">
    <div class="col-lg-8 mb-3">
        <div class="card mb-3">
            <div class="card-header align-items-center">
                <h4 class="card-title mb-0">Open Findings</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered table-hover nowrap w-100"
                        id="pendingChecksheetTable">
                        <thead>
                            <tr>
                                <th>Area</th>
                                <th>Status</th>
                                <th>Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header align-items-center">
                <h4 class="card-title mb-0">Checked</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered table-hover nowrap w-100"
                        id="checkedTable">
                        <thead>
                            <tr>
                                <th>Area</th>
                                <th>Status</th>
                                <th>Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header align-items-center">
                <h4 class="card-title mb-0">Approved</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered table-hover nowrap w-100"
                        id="checksheetTbl">
                        <thead>
                            <tr>
                                <th>Area</th>
                                <th>Status</th>
                                <th>Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 inboxContainer overflow-hidden">
        <div class="card" style="max-height: 450px; overflow-y: auto;">
            <div class="card-body">
                <h5 class="card-title">Incoming Item Request</h5>
                <div class="messages mb-1 p-3 shadow-sm rounded">
                    <p class="card-text mb-1">Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate,
                        labore. 04_AREA_021639</p>
                    <span class="justify-content-between d-flex">
                        <div class="icons">
                            <button type="button" class="btn btn-primary btn-sm">
                                <i class='bx bx-check'></i>
                            </button>

                            <button type="button" class="btn btn-danger btn-sm">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>
                        <h1 style="font-size: 10pt !important;">Jhomell Jaspio</h1>
                    </span>
                </div>

                <div class="messages mb-1 p-3 shadow-sm rounded">
                    <p class="card-text mb-1">Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate,
                        labore. 02_AREA_021639</p>
                    <span class="justify-content-between d-flex">
                        <div class="icons">
                            <button type="button" class="btn btn-primary btn-sm">
                                <i class='bx bx-check'></i>
                            </button>

                            <button type="button" class="btn btn-danger btn-sm">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>
                        <h1 style="font-size: 10pt !important;">Christian Samonte</h1>
                    </span>
                </div>

                <div class="messages mb-1 p-3 shadow-sm rounded">
                    <p class="card-text mb-1">Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate,
                        labore. 01_AREA_021639</p>
                    <span class="justify-content-between d-flex">
                        <div class="icons">
                            <button type="button" class="btn btn-primary btn-sm">
                                <i class='bx bx-check'></i>
                            </button>

                            <button type="button" class="btn btn-danger btn-sm">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>
                        <h1 style="font-size: 10pt !important;">Jhomell Jaspio</h1>
                    </span>
                </div>

                <div class="messages mb-1 p-3 shadow-sm rounded">
                    <p class="card-text mb-1">Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate,
                        labore. 05_AREA_021639</p>
                    <span class="justify-content-between d-flex">
                        <div class="icons">
                            <button type="button" class="btn btn-primary btn-sm">
                                <i class='bx bx-check'></i>
                            </button>

                            <button type="button" class="btn btn-danger btn-sm">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>
                        <h1 style="font-size: 10pt !important;">Jhomell Jaspio</h1>
                    </span>
                </div>

                <div class="messages mb-1 p-3 shadow-sm rounded">
                    <p class="card-text mb-1">Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate,
                        labore. 01_AREA_021639</p>
                    <span class="justify-content-between d-flex">
                        <div class="icons">
                            <button type="button" class="btn btn-primary btn-sm">
                                <i class='bx bx-check'></i>
                            </button>

                            <button type="button" class="btn btn-danger btn-sm">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>
                        <h1 style="font-size: 10pt !important;">Christian Samonte</h1>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALS -->

<!-- CHECK SHEET MODAL -->
<?= view('pages/modals/checksheet_pending') ?>

<script>
    $(document).ready(function () {

        /* select2 configuration */
        /* $('#itemSelect').select2({
            tags: true,
            theme: 'bootstrap-5'
        }); */

        /* ADD FIELD ROW */
        $('#addFieldBtn').on('click', function (e) {
            e.preventDefault();

            let rowCount = $('#checksheetTable tbody tr').length;

            $.ajax({
                url: "<?= base_url('checksheet/addRow') ?>",
                type: "GET",
                data: { rowIndex: rowCount },
                success: function (html) {
                    /* append new row to the table body */
                    $('#checksheetTable tbody').append(html);
                },
                error: function (xhr, status, error) {
                    console.error(error);
                }
            });
        });

        /* GET FINDINGS BY ITEM */
        $(document).on('change', '.select_item', function () {
            let itemId = $(this).val();
            let row = $(this).closest('tr');
            let findingsDropdown = row.find('.findings-dropdown');

            findingsDropdown.html('<option>Loading...</option>');

            if (itemId) {
                $.ajax({
                    url: "<?= base_url('checksheet/get_findings/') ?>" + itemId,  // adjust if you use base_url()
                    method: "GET",
                    dataType: "json",
                    success: function (response) {
                        findingsDropdown.empty().append('<option value="" selected disabled>Select findings...</option>');
                        if (response.length > 0) {
                            $.each(response, function (i, finding) {
                                findingsDropdown.append(
                                    '<option value="' + finding.findings_id + '">' + finding.findings_name + '</option>'
                                );
                            });
                        } else {
                            findingsDropdown.append('<option value="" disabled>No findings found.</option>');
                        }
                    }
                });
            }
        });

        /* GET PENDING DATA */
        $('#pendingChecksheetTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= base_url('checksheet/getChecksheetPending') ?>',
                type: 'POST',
            },
            columns: [
                { data: 'area_name' },
                { data: 'status' },
                { data: 'remarks' },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row){
                        return '<button type="button" class="btn btn-primary btn-sm view-btn" data-bs-target="#reviewCheckSheetModal" data-bs-toggle="modal" data-area-control="' + row.area_control + '" data-area-id="' + row.area_id + '">View</button>';
                    }
                }
            ]
        });

        /* HANDLE VIEW BUTTON CLICK */
        $('#reviewCheckSheetModal').on('show.bs.modal', function (event) {
            // Get the button that triggered the modal
            var button = $(event.relatedTarget);

            // Extract the value from the data-area-control attribute
            var areaControl = button.data('area-control');

            // Get the modal's input field
            var modalControlNumInput = $(this).find('#controlNum');

            // Update the modal's input field with the areaControl value
            modalControlNumInput.val(areaControl);
        });

        let itemCounters = {};

        $(document).on('change', '.select_item', function(){
            let selectedItem = $(this).val();
            let itemText = $(this).find("option:selected").text().trim();
            let subControlInput = $(this).closest('tr').find('input[name="subControl[]"]');

            if(selectedItem){
                if(!itemCounters[selectedItem]){
                    itemCounters[selectedItem] = 1;
                }else{
                    itemCounters[selectedItem]++;
                }

                let formattedNumber = String(itemCounters[selectedItem]).padStart(2, "0");

                let subControl = itemText.toUpperCase().replace(/\s+/g, "-") + "-" + formattedNumber;

                subControlInput.val(subControl);
            }else{
                subControlInput.val("");
            }
        });

        /* form validation */
        $(document).on('change', 'input[type=radio]', function(){
            let row = $(this).closest('tr');
            let isNG = $(this).val() === 'NG';

            let fields = row.find(
                'select[name="findings[]"], input[name="findingImg[]"], textarea[name="remarks[]"], select[name="jobReceiver[]"]'
            );

            if(isNG){
                fields.prop('disabled', false);
                fields.prop('required', true);
            }else{
                fields.prop('disabled', true);
                fields.prop('required', false);
                fields.val(""); // clear values if disabled
            }
        });

        /* submit checksheet */
        $('#checksheetForm').on('submit', function(e){
            e.preventDefault();

            let formData = new FormData(this);

            $.ajax({
                url: '<?= base_url('checksheet/submitChecksheetForm') ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response){
                    if (response.status === "success") {
                        alert(response.message);
                    } else {
                        // handle array of errors if multiple
                        if (Array.isArray(response.messages)) {
                            alert(response.messages.join("\n"));
                        } else {
                            alert(response.messages || "Something went wrong.");
                        }
                    }
                },
                error: function(xhr, status, error){
                    console.error(error);
                    alert("Something went wrong.");
                }
            });
        });

        /* GET CHECKED DATA */
        $('#checkedTable').DataTable({
            processing: true,
            serverSide: true,
            ajax:{
                url: '<?= base_url('checksheet/getCheckedData') ?>',
                type: 'POST',
            },
            columns: [
                { data: 'area_name' },
                { data: 'status' },
                { data: 'remarks' },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row){
                        return '<button type="button" class="btn btn-primary btn-sm view-btn" data-bs-target="#checkedData" data-bs-toggle="modal">View</button>';
                    }
                }
            ]
        });
        /* GET APPROVED DATA */

    });
</script>
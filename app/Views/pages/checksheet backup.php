<div class="row">
    <div class="col-lg-8 mb-3">
        <div class="card mb-3">
            <div class="card-header align-items-center">
                <h4 class="card-title mb-0">Pending</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered table-hover nowrap w-100"
                        id="pendingChecksheetTable">
                        <thead>
                            <tr>
                                <th>Checksheet ID</th>
                                <th>Area</th>
                                <th>Building</th>
                                <!-- <th>Status</th> -->
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
                        id="checkedChecksheetTable">
                        <thead>
                            <tr>
                                <th>Checksheet ID</th>
                                <th>Area</th>
                                <th>Building</th>
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
                        id="approvedChecksheetTable">
                        <thead>
                            <tr>
                                <th>Checksheet ID</th>
                                <th>Area</th>
                                <th>Building</th>
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
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Incoming Item Request</h5>
                <div id="requestItemsContainer" style="max-height: 500px; overflow-y: auto;">
                    </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="checksheetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen" role="document">
        <form id="checksheetForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Checksheet Viewer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <hr>
            <div class="modal-body">
                <input type="hidden" id="checksheet_id" name="checksheet_id">
                
                <div id="checksheetDetailsContainer"></div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save Entry</button>
            </div>
        </form>
    </div>
</div>


<script>
    $(document).ready(function () {
    // Load the incoming item requests for the sidebar
    loadRequestItems();

    // --- DataTable Initializations ---
    const pendingChecksheetTable = $('#pendingChecksheetTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('checksheet/getPending') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'checksheet_id' },
            { data: 'area_name' },
            { data: 'building_name' },
            { data: 'actions' }
        ],
        order: [[0, 'desc']]
    });

    const checkedChecksheetTable = $('#checkedChecksheetTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('checksheet/getChecked') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'checksheet_id' },
            { data: 'area_name' },
            { data: 'building_name' },
            { data: 'actions' }
        ],
        order: [[0, 'desc']]
    });

    const approvedChecksheetTable = $('#approvedChecksheetTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('checksheet/getApproved') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'checksheet_id' },
            { data: 'area_name' },
            { data: 'building_name' },
            { data: 'actions' }
        ],
        order: [[0, 'desc']]
    });

    // --- Event Listeners ---
    $(document).on('click', 'button[onclick^="viewRequest"]', function () {
        const id = $(this).attr('onclick').match(/\d+/)[0];
        viewRequest(id);
    });

    // Handles the form submission
    $('#checksheetForm').submit(function (e) {
        e.preventDefault();
        if ($('#findingsRowsContainer .row-item').length === 0) {
            Swal.fire('Empty Form!', 'Please add at least one item row before saving.', 'warning');
            return;
        }
        const formData = new FormData(this);
        const $btn = $(this).find('button[type="submit"]');
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        $btn.prop('disabled', true).text('Saving...');
        $.ajax({
            url: '<?= base_url('checksheet/saveChecksheetData') ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    Swal.fire('Success!', res.message, 'success');
                    $('#checksheetModal').modal('hide');
                    pendingChecksheetTable.ajax.reload(); // Reload the table
                } else {
                    Swal.fire('Error!', res.message || 'An error occurred.', 'error');
                }
            },
            error: function (xhr) {
                Swal.fire('Request Failed!', xhr.responseJSON ? xhr.responseJSON.message : 'An unknown error occurred.', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Save Entry');
            }
        });
    });

    // UPDATED: Handles the 'Item' dropdown change to update sub-control and load findings
    $('#checksheetDetailsContainer').on('change', '.item-select', function() {
        const selectedOption = $(this).find('option:selected');
        const itemId = selectedOption.val();
        const controlValue = selectedOption.data('control');
        const row = $(this).closest('.row-item');
        const findingsDropdown = row.find('.findings-select');
        const hiddenControlField = row.find('.control-hidden-field');
        const subControlField = row.find('.sub-control-field');
        
        // --- FIX IS HERE ---
        // First, find the judgement toggle in the same row
        const judgementToggle = row.find('.judgement-toggle');
        const isJudgementOK = judgementToggle.is(':checked');
        // --- END FIX ---

        hiddenControlField.val(controlValue);
        if (controlValue) {
            const rowIndex = row.index() + 1;
            const formattedIndex = String(rowIndex).padStart(3, '0');
            subControlField.val(`${controlValue}_${formattedIndex}`);
        } else {
            subControlField.val('');
        }

        const areaName = $('#checksheetDetailsContainer').data('area-name');
        const firstControl = $('#findingsRowsContainer .control-hidden-field[value!=""]').first().val();
        $('#modalTitle').text(`Checksheet Viewer - ${areaName}` + (firstControl ? ` (${firstControl})` : ''));

        if (!itemId) {
            findingsDropdown.html('<option value="">Select item first...</option>').prop('disabled', true);
            return;
        }

        findingsDropdown.html('<option value="">Loading...</option>');
        
        // --- FIX IS HERE ---
        // Only enable the findings dropdown if the judgement is NOT OK (i.e., it's NG)
        if (!isJudgementOK) {
            findingsDropdown.prop('disabled', false);
        }
        // --- END FIX ---
        
        $.ajax({
            url: `<?= base_url('checksheet/getFindingsByItem') ?>/${itemId}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data.length > 0) {
                    let findingsOptions = '<option value="">N/A</option>';
                    response.data.forEach(function(finding) {
                        findingsOptions += `<option value="${finding.findings_id}">${finding.findings_name}</option>`;
                    });
                    findingsDropdown.html(findingsOptions);
                } else {
                    findingsDropdown.html('<option value="">No findings</option>');
                }
            },
            error: function() {
                findingsDropdown.html('<option value="">Error!</option>');
            }
        });
    });

    // NEW: Handles the color and text change for the OK/NG toggle switch
    $('#checksheetDetailsContainer').on('change', '.judgement-toggle', function() {
        const isChecked = $(this).is(':checked');
        const row = $(this).closest('.row-item'); // Get the parent table row

        // Find all the elements within that specific row
        const label = row.find('.form-check-label');
        const hiddenInput = row.find('input[type="hidden"][name="status[]"]');
        const findingsDropdown = row.find('.findings-select');
        const imageUploadContainer = row.find('.image-upload-container');
        const driDropdown = row.find('select[name="dri_id[]"]');
        const remarksInput = row.find('input[name="remarks[]"]');

        if (isChecked) {
            // --- If Judgement is OK ---
            label.text('OK').removeClass('text-danger').addClass('text-success');
            hiddenInput.val(1);
            
            // Disable fields and clear their values
            findingsDropdown.prop('disabled', true).val('');
            driDropdown.prop('disabled', true).val('');
            remarksInput.prop('disabled', true).val('');

            // Special handling for the image input to reset it
            const fileInput = imageUploadContainer.find('.finding-image-input');
            const previewContainer = imageUploadContainer.find('.image-preview-container');
            const fileInputLabel = imageUploadContainer.find('.file-input-label');
            fileInput.val('').prop('disabled', true);
            previewContainer.hide();
            fileInputLabel.show().css('pointer-events', 'none').addClass('disabled'); // Visually disable the label

        } else {
            // --- If Judgement is NG ---
            label.text('NG').removeClass('text-success').addClass('text-danger');
            hiddenInput.val(0);

            // Re-enable fields
            // Note: The findings dropdown will only be usable if an item is selected.
            findingsDropdown.prop('disabled', false);
            driDropdown.prop('disabled', false);
            remarksInput.prop('disabled', false);
            
            // Re-enable the image input
            imageUploadContainer.find('.finding-image-input').prop('disabled', false);
            imageUploadContainer.find('.file-input-label').css('pointer-events', 'auto').removeClass('disabled');
        }
    });

   $('#checksheetDetailsContainer').on('click', '.remove-row-btn', function() {
        $(this).closest('.row-item').remove();
    });

    // Handles displaying the image preview when a file is selected
    $('#checksheetDetailsContainer').on('change', '.finding-image-input', function(event) {
        const input = this;
        const container = $(this).closest('.image-upload-container');
        const previewContainer = container.find('.image-preview-container');
        const fileInputLabel = container.find('.file-input-label');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.find('img').attr('src', e.target.result);
                fileInputLabel.hide();
                previewContainer.css('display', 'flex');
            };
            reader.readAsDataURL(input.files[0]);
        }
    });

    // Handles removing the image preview and showing the 'Attach' button again
    $('#checksheetDetailsContainer').on('click', '.remove-image-btn', function() {
        const container = $(this).closest('.image-upload-container');
        const previewContainer = container.find('.image-preview-container');
        const fileInputLabel = container.find('.file-input-label');
        const fileInput = container.find('.finding-image-input');

        fileInput.val('');
        
        previewContainer.hide();
        fileInputLabel.show();
    });
});

    function loadRequestItems() {
        $.ajax({
            url: '<?= base_url('checksheet/getPendingRequests') ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                const container = $('#requestItemsContainer');
                container.empty(); 

                if (response.status === 'success' && response.data.length > 0) {
                    response.data.forEach(function(item) {
                        const itemHtml = `
                            <div class="messages mb-1 p-3 shadow-sm rounded">
                                <p class="card-text mb-1"><strong>${item.area_name}</strong></p>
                                <p class="card-text mb-1">${item.item_name}</p>
                                <p class="card-text mb-1">${item.description}</p><br>
                                <span class="justify-content-between d-flex">
                                    <div class="icons">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="approveRequest(${item.request_id})">
                                            <i class='bx bx-check'></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="rejectRequest(${item.request_id})">
                                            <i class='bx bx-x'></i>
                                        </button>
                                    </div>
                                    <h1 style="font-size: 10pt !important;">${item.firstname} ${item.lastname}</h1>
                                </span>
                            </div>
                        `;
                        container.append(itemHtml);
                    });
                } else {
                    container.html('<p class="text-center text-muted p-3">No incoming item requests.</p>');
                }
            },
            error: function() {
                const container = $('#requestItemsContainer');
                container.html('<p class="text-center text-danger p-3">Failed to load requests.</p>');
            }
        });
    }

    function approveRequest(id) {
        Swal.fire({
            title: 'Approve Request',
            text: "Are you sure you want to approve this item request?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url('checksheet/updateRequestStatus') ?>',
                    type: 'POST',
                    data: {
                        request_id: id,
                        status: 1,
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>' // CSRF Protection
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Approved!', res.message, 'success');
                            loadRequestItems(); // Refresh the list to remove the approved item
                        } else {
                            Swal.fire('Error!', res.message || 'Request failed.', 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON ? xhr.responseJSON.message : 'An unknown error occurred.', 'error');
                    }
                });
            }
        });
    }

    function rejectRequest(id) {
        Swal.fire({
            title: 'Reject Request',
            text: 'Please provide a reason for rejection:',
            icon: 'warning',
            input: 'textarea',
            inputPlaceholder: 'Type your reason here...',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, reject it!',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to write a reason for rejection!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url('checksheet/updateRequestStatus') ?>',
                    type: 'POST',
                    data: {
                        request_id: id,
                        status: 2, // 2 for Rejected
                        remarks: result.value, // Get remarks from the Swal input
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>' // CSRF Protection
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Rejected!', res.message, 'success');
                            loadRequestItems(); // Refresh the list to remove the rejected item
                        } else {
                            Swal.fire('Error!', res.message || 'Request failed.', 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON ? xhr.responseJSON.message : 'An unknown error occurred.', 'error');
                    }
                });
            }
        });
    }

function viewRequest(id) {
    $.ajax({
        url: `<?= base_url('checksheet/getDropdownData') ?>/${id}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status !== 'success') {
                Swal.fire('Error!', 'Could not load data for the checksheet.', 'error');
                return;
            }

            // --- FIX IS HERE ---
            const items = response.data.items;
            const dris = response.data.dris; // This line was missing
            const areaName = response.data.area_name;
            // --- END FIX ---

            const detailsContainer = $('#checksheetDetailsContainer');
            detailsContainer.data('area-name', areaName);
            detailsContainer.empty(); // This prevents content from duplicating on second view

            const formHtml = `
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Item</th>
                                <th style="width: 15%;">Sub-control</th>
                                <th style="width: 10%;">Judgement</th>
                                <th style="width: 15%;">Findings</th>
                                <th style="width: 15%;">Image</th>
                                <th style="width: 15%;">DRI</th>
                                <th style="width: 15%;">Remarks</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="findingsRowsContainer">
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-success btn-sm" id="addRowBtn">
                        <i class='bx bx-plus'></i> Add Row
                    </button>
                </div>
            `;
            detailsContainer.html(formHtml);

            addRow(items, dris);

            detailsContainer.off('click', '#addRowBtn').on('click', '#addRowBtn', function() {
                addRow(items, dris);
            });

            // Show the modal
            $('#modalTitle').text(`Checksheet Viewer - ${areaName}`);
            $('#checksheet_id').val(id);
            $('#checksheetModal').modal('show');
        },
        error: function() {
            Swal.fire('Error!', 'Failed to fetch initial checksheet data.', 'error');
        }
    });
}

function addRow(items = [], dris = []) {
    const rowsContainer = $('#findingsRowsContainer');

    let itemOptions = items.map(item => 
        `<option value="${item.item_id}" data-control="${item.control || ''}">${item.item_name}</option>`
    ).join('');
    
    let driOptions = dris.map(dri => `<option value="${dri.dri_id}">${dri.fullname}</option>`).join('');

    const newRowHtml = `
        <tr class="row-item">
            <td>
                <select class="form-select form-select-sm item-select" name="item_id[]" required>
                    <option value="" selected disabled>Choose Item...</option>
                    ${itemOptions}
                </select>
                <input type="hidden" class="control-hidden-field" name="control[]" value="">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm sub-control-field" name="sub_control[]" placeholder="Sub-control" readonly>
            </td>
            <td>
                <div class="form-check form-switch d-flex justify-content-center">
                    <input class="form-check-input judgement-toggle" type="checkbox" role="switch" checked>
                    <label class="form-check-label ms-2 text-success">OK</label>
                    <input type="hidden" name="status[]" value="1">
                </div>
            </td>
            <td>
                <select class="form-select form-select-sm findings-select" name="findings_id[]" disabled>
                    <option value="" selected>Select item first...</option>
                </select>
            </td>
            <td>
                <div class="image-upload-container">
                    <label class="btn btn-sm btn-outline-secondary file-input-label w-100">
                        <i class='bx bx-paperclip'></i> Attach
                        <input type="file" class="finding-image-input" name="finding_image[]" accept="image/*" style="display: none;">
                    </label>
                    <div class="image-preview-container align-items-center" style="display: none;">
                        <img src="" alt="Preview" class="img-thumbnail me-2" style="max-width: 80px; max-height: 40px;">
                        <button type="button" class="btn btn-danger btn-sm remove-image-btn">&times;</button>
                    </div>
                </div>
            </td>
            <td>
                <select class="form-select form-select-sm" name="dri_id[]" required>
                    <option value="" selected disabled>Choose DRI...</option>
                    ${driOptions}
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="remarks[]" placeholder="Remarks...">
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-danger btn-sm remove-row-btn">
                    <i class='bx bx-trash'></i>
                </button>
            </td>
        </tr>
    `;
    
    // Append the new row to the table
    const newRow = $(newRowHtml).appendTo(rowsContainer);

    // --- FIX IS HERE ---
    // Trigger the change event on the new toggle to set the initial disabled state
    newRow.find('.judgement-toggle').trigger('change');
}
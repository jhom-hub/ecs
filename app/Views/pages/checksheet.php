<div class="row">
    <div class="col-lg-8 mb-3">
        <div class="card mb-3">
            <div class="card-header align-items-center">
                <h4 class="card-title mb-0">Checksheets</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered table-hover nowrap w-100" id="ChecksheetTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Area</th>
                                <th>Building</th>
                                <th>Status</th>
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
                <div id="requestItemsContainer" style="max-height: 500px; overflow-y: auto;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="checksheetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
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

<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalTitle">Review Checksheet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <h5>Audit Details</h5>
                        <table class="table table-bordered table-sm">
                            <tbody>
                                <tr>
                                    <th style="width: 15%;">Building</th>
                                    <td id="reviewBuilding"></td>
                                    <th style="width: 15%;">Area</th>
                                    <td id="reviewArea"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Sub-Control</th>
                                <th>Judgement</th>
                                <th>Findings</th>
                                <th>DRI</th>
                                <th>Remarks</th>
                                <th>Findingss Image</th>
                                <th>Action Image</th>
                                <th>Action Description</th>
                            </tr>
                        </thead>
                        <tbody id="reviewItemsContainer">
                            </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function () {
    loadRequestItems();

    const checksheetTable = $('#ChecksheetTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('checksheet/getAll') ?>',
            type: 'POST'
        },
        columns: [
            { data: 'checksheet_id' },
            { data: 'area_name' },
            { data: 'building_name' },
            { data: 'status' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
        columnDefs: [
            {
                className: 'dtr-control', 
                orderable: false,
                targets: 0
            },
            {
                targets: 3,
                render: function(data, type, row) {
                    switch (data) {
                        case '0': return '<span class="badge bg-danger">Pending</span>';
                        case '1': return '<span class="badge bg-success">Checked</span>';
                        case '2': return '<span class="badge bg-primary">Action Taken</span>';
                        default: return '<span class="badge bg-secondary">Unknown</span>';
                    }
                }
            },
            {
                targets: 4,
                render: function(data, type, row) {
                    if (row.status == '0') {
                        return `<button type="button" class="btn btn-sm btn-primary" onclick="viewRequest(${row.checksheet_id})">View</button>`;
                    } else if (row.status == '1') {
                        return `<button type="button" class="btn btn-sm btn-secondary" onclick="reviewRequest(${row.checksheet_id})">Review</button>`;
                    } else {
                        return `<button type="button" class="btn btn-sm btn-secondary" onclick="reviewRequest(${row.checksheet_id})">Review</button>`;
                    }
                }
            }
        ]
    });

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
                    checksheetTable.ajax.reload(); 
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

    $('#checksheetDetailsContainer').on('change', '.item-select', function() {
        const selectedOption = $(this).find('option:selected');
        const itemId = selectedOption.val();
        const controlValue = selectedOption.data('control');
        const row = $(this).closest('.row-item');
        const findingsDropdown = row.find('.findings-select');
        const hiddenControlField = row.find('.control-hidden-field');
        const subControlField = row.find('.sub-control-field');
        const judgementToggle = row.find('.judgement-toggle');
        const priorityToggle = row.find('.priority-toggle');
        const isJudgementOK = judgementToggle.is(':checked');

        hiddenControlField.val(controlValue);
        if (controlValue) {
            const rowIndex = row.index();
            const formattedIndex = String(rowIndex + 1).padStart(3, '0');
            subControlField.val(`${controlValue}_${formattedIndex}`);
        } else {
            subControlField.val('');
        }

        const areaName = $('#checksheetDetailsContainer').data('area-name');
        const firstControl = $('#findingsRowsContainer .control-hidden-field[value!=""]').first().val();
        $('#modalTitle').text(`Checksheet Viewer - ${areaName}` + (firstControl ? ` (${firstControl})` : ''));

        if (!itemId) {
            findingsDropdown.html('').prop('disabled', true).trigger('change'); // Notify Select2
            return;
        }

        findingsDropdown.html('').prop('disabled', true);
        
        $.ajax({
            url: `<?= base_url('checksheet/getFindingsByItem') ?>/${itemId}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                let findingsOptions = '';
                if (response.status === 'success' && response.data.length > 0) {
                    response.data.forEach(function(finding) {
                        findingsOptions += `<option value="${finding.findings_id}">${finding.findings_name}</option>`;
                    });
                }
                findingsDropdown.html(findingsOptions);

                if (!isJudgementOK) {
                    findingsDropdown.prop('disabled', false);
                }
                findingsDropdown.trigger('change'); 
            },
            error: function() {
                findingsDropdown.html('').trigger('change');
            }
        });
    });

    $('#checksheetDetailsContainer').on('change', '.judgement-toggle', function() {
        const isChecked = $(this).is(':checked');
        const row = $(this).closest('.row-item');
        const label = row.find('.form-check-label');
        const hiddenInput = row.find('input[type="hidden"][name="status[]"]');
        const findingsDropdown = row.find('.findings-select');
        const imageUploadContainer = row.find('.image-upload-container');
        const driDropdown = row.find('select[name="dri_id[]"]');
        const remarksInput = row.find('input[name="remarks[]"]');

        if (isChecked) {
            label.text('OK').removeClass('text-danger').addClass('text-success');
            hiddenInput.val(1);
            findingsDropdown.prop('disabled', true).val(null).trigger('change'); 
            driDropdown.prop('disabled', true).val('');
            remarksInput.prop('disabled', true).val('');
            const fileInput = imageUploadContainer.find('.finding-image-input');
            const previewContainer = imageUploadContainer.find('.image-preview-container');
            const fileInputLabel = imageUploadContainer.find('.file-input-label');
            fileInput.val('').prop('disabled', true);
            previewContainer.hide();
            fileInputLabel.show().css('pointer-events', 'none').addClass('disabled');
        } else {
            label.text('NG').removeClass('text-success').addClass('text-danger');
            hiddenInput.val(0);
            findingsDropdown.prop('disabled', false).trigger('change');
            driDropdown.prop('disabled', false);
            remarksInput.prop('disabled', false);
            imageUploadContainer.find('.finding-image-input').prop('disabled', false);
            imageUploadContainer.find('.file-input-label').css('pointer-events', 'auto').removeClass('disabled');
        }
    });

    $('#checksheetDetailsContainer').on('change', '.priority-toggle', function() {
        const isChecked = $(this).is(':checked');
        const row = $(this).closest('.row-item');
        const hiddenInput = row.find('input[type="hidden"][name="priority[]"]');

        hiddenInput.val(isChecked ? 1 : 0);
    });

    $('#checksheetDetailsContainer').on('click', '.remove-row-btn', function() {
        const row = $(this).closest('.row-item');
        row.find('.findings-select').select2('destroy');
        row.remove();
    });

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

        // Clear the file input's value so it's not submitted
        fileInput.val('');
        
        // Hide the preview and show the attach button
        previewContainer.hide();
        fileInputLabel.show();
    });
});

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
            const items = response.data.items;
            const dris = response.data.dris;
            const areaName = response.data.area_name;
            const detailsContainer = $('#checksheetDetailsContainer');
            detailsContainer.data('area-name', areaName);
            detailsContainer.empty();
            const formHtml = `
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Item</th>
                                <th style="width: 15%;">Sub-control</th>
                                <th style="width: 10%;">Judgement</th>
                                <th style="width: 20%;">Findings</th>
                                <th style="width: 10%;">Image</th>
                                <th style="width: 15%;">DRI</th>
                                <th style="width: 15%;">Remarks</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="findingsRowsContainer"></tbody>
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
    const rowIndex = rowsContainer.children('tr').length;

    let itemOptions = items.map(item => 
        `<option value="${item.item_id}" data-control="${item.control || ''}">${item.item_name}</option>`
    ).join('');
    
    let driOptions = dris.map(dri => 
        `<option value="${dri.department_id}">${dri.department_name}</option>`
    ).join('');

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
                <select class="form-select form-select-sm findings-select" name="findings_id[${rowIndex}][]" multiple="multiple" disabled>
                </select>
            </td>
            <td>
                <div class="image-upload-container">
                    <label class="btn btn-sm btn-outline-secondary file-input-label w-100">
                        <i class='bx bx-paperclip'></i> Attach
                        <input 
                            type="file" 
                            class="finding-image-input" 
                            name="finding_image[]" 
                            accept="image/*" 
                            capture="environment" 
                            style="display: none;"
                        >
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
            <td>
                <div class="form-check form-switch d-flex justify-content-center">
                    <input class="form-check-input priority-toggle" type="checkbox" role="switch">
                    <input type="hidden" name="priority[]">
                </div>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-danger btn-sm remove-row-btn">
                    <i class='bx bx-trash'></i>
                </button>
            </td>
        </tr>
    `;
    
    const newRow = $(newRowHtml).appendTo(rowsContainer);
    
    newRow.find('.findings-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select item first...',
        dropdownParent: $('#checksheetModal') // Important for search to work in a modal
    });

    newRow.find('.judgement-toggle').trigger('change');
}

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
                                <th style="width: 10%;">Image</th>
                                <th style="width: 15%;">DRI</th>
                                <th style="width: 15%;">Remarks</th>
                                <th style="width: 5%;">Priority</th>
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

function reviewRequest(id) {
    $.ajax({
        url: `<?= base_url('checksheet/getChecksheetReviewData') ?>/${id}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status !== 'success') {
                Swal.fire('Error!', response.message || 'Could not load review data.', 'error');
                return;
            }

            const info = response.data.info;
            const items = response.data.items;

            $('#reviewModalTitle').text(`Review Checksheet #${info.checksheet_id}`);
            $('#reviewBuilding').text(info.building_name);
            $('#reviewArea').text(info.area_name);

            const itemsContainer = $('#reviewItemsContainer');
            itemsContainer.empty();

            if (items.length > 0) {
                items.forEach(item => {
                    const judgementBadge = item.status == '2' 
                        ? '<span class="badge bg-success">OK</span>' 
                        : '<span class="badge bg-danger">NG</span>';

                    const findingImageHtml = item.finding_image 
                        ? `<a href="${item.finding_image}" target="_blank"><img src="${item.finding_image}" class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;"></a>`
                        : 'N/A';
                        
                    const actionImageHtml = item.action_image 
                        ? `<a href="${item.action_image}" target="_blank"><img src="${item.action_image}" class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;"></a>`
                        : 'N/A';

                    const rowHtml = `
                        <tr>
                            <td>${item.item_name || 'N/A'}</td>
                            <td>${item.sub_control || 'N/A'}</td>
                            <td>${judgementBadge}</td>
                            <td>${item.findings_list || 'N/A'}</td>
                            <td>${item.dri_name || 'N/A'}</td>
                            <td>${item.remarks || 'N/A'}</td>
                            <td>${findingImageHtml}</td>
                            <td>${actionImageHtml}</td>
                            <td>${item.action_description || 'N/A'}</td>
                        </tr>
                    `;
                    itemsContainer.append(rowHtml);
                });
            } else {
                itemsContainer.html('<tr><td colspan="9" class="text-center">No items found for this checksheet.</td></tr>');
            }
            
            $('#reviewModal').modal('show');
        },
        error: function() {
            Swal.fire('Error!', 'Failed to fetch checksheet review data.', 'error');
        }
    });
}
</script>
<style>
    .swal-topmost {
        z-index: 99999 !important;
    }

    .swal2-toast-container {
        z-index: 99999 !important;
    }

    .swal2-textarea {
        z-index: 99999 !important;
    }

    .activity-link {
        text-decoration: none;
        color: inherit;
    }

    .activity-link.messages:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }

    .finding-tag {
        display: inline-flex;
        align-items: center;
        padding: 0.3em 0.6em;
    }

    .remove-finding-tag {
        font-weight: bold;
        margin-left: 6px;
        line-height: 1;
    }

    .remove-finding-tag:hover {
        color: #fff !important;
        opacity: 0.8;
    }

    .image-viewport {
        position: relative;
        width: 100%;
        max-width: 350px;
        height: 260px;
        border: 1px solid #dee2e6;
        background-color: #000;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .image-viewport img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .comparison-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #6c757d;
    }

    #magnifierWindow {
        position: fixed;
        top: 20px;
        right: 20px;
        width: 400px;
        height: 400px;
        border: 3px solid #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        background-repeat: no-repeat;
        background-color: #000;
        display: none;
        z-index: 99999;
        will-change: background-position;
    }
</style>

<div id="magnifierWindow"></div>

<div class="row">
    <div class="col-lg-8 mb-3">
        <div class="card mb-3">
            <div class="card-header align-items-center">
                <h4 class="card-title mb-0">Checksheet Forms</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered table-hover nowrap w-100"
                        id="ChecksheetTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Area</th>
                                <th>Building</th>
                                <th>⚠️</th>
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
    <div class="col-lg-4 inboxContainer overflow-hidden mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">On-Hold Checksheets</h5>
                <div class="input-group mb-3"><input type="text" id="activitySearchInput"
                        class="form-control form-control-sm" placeholder="Search..."><button
                        class="btn btn-outline-secondary btn-sm" type="button" id="activitySearchBtn"><i
                            class='bx bx-search'></i></button></div>
                <div id="checksheetActivityContainer" style="min-height: 350px; max-height: 350px; overflow-y: auto;">
                </div>
                <nav class="d-flex justify-content-between align-items-center mt-3"><button
                        class="btn btn-secondary btn-sm" id="activityPrevBtn" disabled>Previous</button><span
                        id="activityPageInfo" class="text-muted small"></span><button class="btn btn-secondary btn-sm"
                        id="activityNextBtn" disabled>Next</button></nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="checksheetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <form id="checksheetForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Checksheet Viewer</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <hr>
            <div class="modal-body"><input type="hidden" id="checksheet_id" name="checksheet_id">
                <div id="checksheetDetailsContainer"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary"
                    data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save
                    Entry</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalTitle">Review Checksheet</h5><button type="button"
                    class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="review_checksheet_id" name="checksheet_id">
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
                                <th>Judgement</th>
                                <th>Findings</th>
                                <th>DRI</th>
                                <th>Remarks</th>
                                <th>Findings Img</th>
                                <th>Action Img</th>
                                <th>Desc</th>
                                <th>Feedback</th>
                                <th>Action</th>
                                <th class="text-center"><input class="form-check-input" type="checkbox"
                                        id="selectAllItems"></th>
                            </tr>
                        </thead>
                        <tbody id="reviewItemsContainer"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="approveSelectedBtn">Approve Selected</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="activityDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verify Corrective Action</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="verify_data_id">
                <input type="hidden" id="verify_checksheet_id">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-3">Audit Details</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <tbody>
                                    <tr>
                                        <th class="w-25">Building</th>
                                        <td><span id="verifyBuilding"></span></td>
                                    </tr>
                                    <tr>
                                        <th>Area</th>
                                        <td><span id="verifyArea"></span></td>
                                    </tr>
                                    <tr>
                                        <th>Item</th>
                                        <td><span id="verifyItem"></span></td>
                                    </tr>
                                    <tr>
                                        <th>Findings</th>
                                        <td><span id="verifyFindings"></span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row mt-3 g-4">
                    <div class="col-12 col-lg-7">
                        <div class="row">
                            <div class="col-12 col-md-5 mb-3 mb-md-0 text-center">
                                <h6>Finding Image</h6>
                                <div id="verifyFindingImageContainer" class="image-viewport"></div>
                            </div>
                            <div class="col-md-2 comparison-arrow d-none d-md-flex">&#10140;</div>
                            <div class="col-12 col-md-5 text-center">
                                <h6>Action Image</h6>
                                <div id="verifyActionImageContainer" class="image-viewport"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <h5>Action Description</h5>
                        <p id="verifyActionDescription" class="p-3 bg-light rounded border" style="min-height: 260px;">
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="approveActivityBtn">Approve</button>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof window.currentActivityPage === 'undefined') {
        var currentActivityPage = 1;
        var currentActivitySearch = '';
    }

    $(document).ready(function () {
        const checksheetTable = $('#ChecksheetTable').DataTable({
            processing: true, serverSide: true,
            ajax: { url: '<?= base_url('checksheet/getAll') ?>', type: 'POST' },
            columns: [
                { data: 'checksheet_id' }, { data: 'area_name' }, { data: 'building_name' },
                { data: 'priority_count' }, { data: 'status' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            responsive: { details: { type: 'column', target: 'tr' } },
            columnDefs: [
                { className: 'dtr-control', orderable: false, targets: 0 },
                {
                    targets: 4,
                    render: function (data, type, row) {
                        switch (data) {
                            case '0': return '<span class="badge bg-danger">Pending</span>';
                            case '1': return '<span class="badge bg-secondary">Checked</span>';
                            case '2': return '<span class="badge bg-primary">For Verification</span>';
                            case '3': return '<span class="badge bg-success">DONE</span>';
                            default: return '<span class="badge bg-secondary">Unknown</span>';
                        }
                    }
                },
                {
                    targets: 5,
                    render: function (data, type, row) {
                        return (row.status == '0')
                            ? `<button type="button" class="btn btn-sm btn-primary" onclick="viewRequest(${row.checksheet_id})">View</button>`
                            : `<button type="button" class="btn btn-sm btn-secondary" onclick="reviewRequest(${row.checksheet_id})">Review</button>`;
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
                url: '<?= base_url('checksheet/saveChecksheetData') ?>', type: 'POST', data: formData,
                processData: false, contentType: false, dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Success!', res.message, 'success');
                        $('#checksheetModal').modal('hide');
                        checksheetTable.ajax.reload();
                    } else { Swal.fire('Error!', res.message || 'An error occurred.', 'error'); }
                },
                error: function (xhr) { Swal.fire('Request Failed!', xhr.responseJSON ? xhr.responseJSON.message : 'An unknown error occurred.', 'error'); },
                complete: function () { $btn.prop('disabled', false).text('Save Entry'); }
            });
        });

        $('#checksheetDetailsContainer').on('change', '.item-select', function () {
            const selectedOption = $(this).find('option:selected'), itemId = selectedOption.val(), controlValue = selectedOption.data('control');
            const row = $(this).closest('.row-item'), findingsDropdown = row.find('.findings-select');
            const hiddenControlField = row.find('.control-hidden-field'), subControlField = row.find('.sub-control-field');
            const isJudgementOK = row.find('.judgement-toggle').is(':checked');
            hiddenControlField.val(controlValue);
            subControlField.val(controlValue ? `${controlValue}_${String(row.index() + 1).padStart(3, '0')}` : '');
            if (!itemId) { findingsDropdown.html('<option value="">Choose Finding...</option>').prop('disabled', true); return; }
            findingsDropdown.html('<option value="">Loading...</option>').prop('disabled', true);
            $.getJSON(`<?= base_url('checksheet/getFindingsByItem') ?>/${itemId}`, function (response) {
                let findingsOptions = '<option value="">Choose Finding...</option>';
                if (response.status === 'success' && response.data.length > 0) {
                    response.data.forEach(function (finding) { findingsOptions += `<option value="${finding.findings_id}">${finding.findings_name}</option>`; });
                }
                findingsDropdown.html(findingsOptions);
                if (!isJudgementOK) { findingsDropdown.prop('disabled', false); }
            }).fail(() => findingsDropdown.html('<option value="">Error loading</option>'));
        });
        $('#checksheetDetailsContainer').on('change', '.judgement-toggle', function () {
            const isChecked = $(this).is(':checked'), row = $(this).closest('.row-item'), label = row.find('.form-check-label'), hiddenInput = row.find('input[type="hidden"][name="status[]"]');
            const findingsDropdown = row.find('.findings-select'), imageUploadContainer = row.find('.image-upload-container'), driDropdown = row.find('select[name="dri_id[]"]');
            const remarksInput = row.find('input[name="remarks[]"]'), priorityToggle = row.find('.priority-toggle'), priorityHidden = row.find('input[type="hidden"][name="priority[]"]');
            if (isChecked) {
                label.text('OK').removeClass('text-danger').addClass('text-success'); hiddenInput.val(2);
                findingsDropdown.prop('disabled', true).val(''); driDropdown.prop('disabled', true).val('');
                remarksInput.prop('disabled', true).val(''); priorityToggle.prop('checked', false).prop('disabled', true); priorityHidden.val(0);
                const fileInput = imageUploadContainer.find('.finding-image-input'), previewContainer = imageUploadContainer.find('.image-preview-container'), fileInputLabel = imageUploadContainer.find('.file-input-label');
                fileInput.val('').prop('disabled', true); previewContainer.hide(); fileInputLabel.show().css('pointer-events', 'none').addClass('disabled');
            } else {
                label.text('NG').removeClass('text-success').addClass('text-danger'); hiddenInput.val(0);
                findingsDropdown.prop('disabled', false); driDropdown.prop('disabled', false);
                remarksInput.prop('disabled', false); priorityToggle.prop('disabled', false);
                imageUploadContainer.find('.finding-image-input').prop('disabled', false);
                imageUploadContainer.find('.file-input-label').css('pointer-events', 'auto').removeClass('disabled');
            }
        });
        $('#checksheetDetailsContainer').on('change', '.priority-toggle', function () { $(this).closest('.row-item').find('input[type="hidden"][name="priority[]"]').val($(this).is(':checked') ? 1 : 0); });
        $('#checksheetDetailsContainer').on('click', '.remove-row-btn', function () { $(this).closest('.row-item').remove(); });
        $('#checksheetDetailsContainer').on('change', '.finding-image-input', function (event) {
            const input = this, container = $(this).closest('.image-upload-container'), previewContainer = container.find('.image-preview-container'), fileInputLabel = container.find('.file-input-label');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) { previewContainer.find('img').attr('src', e.target.result); fileInputLabel.hide(); previewContainer.css('display', 'flex'); };
                reader.readAsDataURL(input.files[0]);
            }
        });
        $('#checksheetDetailsContainer').on('click', '.remove-image-btn', function () {
            const container = $(this).closest('.image-upload-container'), previewContainer = container.find('.image-preview-container'), fileInputLabel = container.find('.file-input-label');
            container.find('.finding-image-input').val(''); previewContainer.hide(); fileInputLabel.show();
        });

        loadChecksheetActivity(currentActivityPage, currentActivitySearch);
        $('#activitySearchBtn').on('click', function () { currentActivitySearch = $('#activitySearchInput').val(); currentActivityPage = 1; loadChecksheetActivity(currentActivityPage, currentActivitySearch); });
        $('#activitySearchInput').on('keyup', function (e) { if (e.key === 'Enter') $('#activitySearchBtn').click(); });
        $('#activityPrevBtn').on('click', function () { if (currentActivityPage > 1) { currentActivityPage--; loadChecksheetActivity(currentActivityPage, currentActivitySearch); } });
        $('#activityNextBtn').on('click', function () { $(this).prop('disabled', true); currentActivityPage++; loadChecksheetActivity(currentActivityPage, currentActivitySearch); });

        $('#activityDetailModal').on('hidden.bs.modal', function () {
            const findContainer = document.getElementById('verifyFindingImageContainer');
            const actionContainer = document.getElementById('verifyActionImageContainer');
            if (findContainer && findContainer.magnifierInstance) findContainer.magnifierInstance.destroy();
            if (actionContainer && actionContainer.magnifierInstance) actionContainer.magnifierInstance.destroy();
        });

        $('#selectAllItems').on('change', function () {
            $('.item-approve-check').prop('checked', $(this).is(':checked'));
        });

        $('#approveSelectedBtn').on('click', function () {
            const checksheetId = $('#review_checksheet_id').val();
            const dataIds = $('.item-approve-check:checked').map(function () {
                return $(this).val();
            }).get();

            if (dataIds.length === 0) {
                Swal.fire('No Items Selected', 'Please select at least one item to approve.', 'warning');
                return;
            }

            $.ajax({
                url: '<?= base_url('checksheet/approveItems') ?>',
                type: 'POST',
                data: {
                    checksheet_id: checksheetId,
                    data_ids: dataIds,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        $('#reviewModal').modal('hide');
                        Swal.fire('Approved!', res.message, 'success');
                        checksheetTable.ajax.reload();
                    } else {
                        Swal.fire('Error!', res.message || 'An error occurred.', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error!', 'An unknown error occurred.', 'error');
                }
            });
        });

        $('#approveActivityBtn').on('click', function () {
            const dataId = $('#verify_data_id').val();
            const checksheetId = $('#verify_checksheet_id').val();

            if (!dataId || !checksheetId) {
                Swal.fire('Error', 'Missing necessary data to perform approval.', 'error');
                return;
            }

            $.ajax({
                url: '<?= base_url('checksheet/approveItems') ?>',
                type: 'POST',
                data: {
                    checksheet_id: checksheetId,
                    data_ids: [dataId],
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        $('#activityDetailModal').modal('hide');
                        Swal.fire('Approved!', res.message, 'success');
                        checksheetTable.ajax.reload();
                        loadChecksheetActivity(1, '');
                    } else {
                        Swal.fire('Error!', res.message || 'An error occurred.', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error!', 'An unknown error occurred.', 'error');
                }
            });
        });

    });

    function timeAgo(dateString) { const date = new Date(dateString); const now = new Date(); const seconds = Math.round((now - date) / 1000); const minutes = Math.round(seconds / 60); const hours = Math.round(minutes / 60); const days = Math.round(hours / 24); if (seconds < 60) return `Just now`; if (minutes < 60) return `${minutes} min ago`; if (hours < 24) return `${hours} hr ago`; return `${days} days ago`; }

    function loadChecksheetActivity(page, search) {
        const c = $('#checksheetActivityContainer'), pI = $('#activityPageInfo'), pB = $('#activityPrevBtn'), nB = $('#activityNextBtn');
        c.html('<div class="text-center p-5"><span class="spinner-border spinner-border-sm"></span> Loading...</div>');
        pI.text(''); pB.prop('disabled', true); nB.prop('disabled', true);
        $.getJSON('<?= base_url('inbox/getHoldActivity') ?>', { page: page, search: search }, function (res) {
            c.empty();
            if (res.status === 'success' && res.data.length > 0) {
                res.data.forEach(function (item) {
                    let sT = '';

                    if (item.status == 0) {
                        sT = `<strong>${item.item_name}</strong> in <strong>${item.area_name}</strong> was put on <span class="badge bg-danger">HOLD</span>.`;
                    } else if (item.status == 1) {
                        sT = `Corrective action <span class="badge bg-primary">FOR VERIFICATION</span>: <strong>${item.item_name}</strong> in <strong>${item.area_name}</strong>.`;
                    } else if (item.status == 2) {
                        sT = `Corrective action <span class="badge bg-success">COMPLETE</span>: <strong>${item.item_name}</strong> in <strong>${item.area_name}</strong>.`;
                    }

                    const itemHtml = `<div class="messages mb-1 p-3 shadow-sm rounded activity-link" onclick="viewActivityDetail(${item.data_id})"><p class="card-text mb-1">${sT}</p><p class="card-text text-muted fst-italic mb-2">"${item.action_description}"</p><div class="d-flex justify-content-between align-items-center"><small class="text-muted">By: ${item.department_name || '---'}</small><small class="text-primary">${timeAgo(item.created_at)}</small></div></div>`;
                    c.append(itemHtml);
                });
                const pg = res.pagination; pI.text(`Page ${pg.currentPage} of ${pg.totalPages}`);
                pB.prop('disabled', pg.currentPage <= 1); nB.prop('disabled', pg.currentPage >= pg.totalPages);
            } else { c.html('<p class="text-center text-muted p-5">No matching activity found.</p>'); pI.text('Page 0 of 0'); }
        }).fail(function () { c.html('<p class="text-center text-danger p-5">Failed to load activity.</p>'); });
    }

    function createMagnifier(containerEl, zoom) {
        if (!containerEl) return;
        const img = containerEl.querySelector('img');
        if (!img) return;
        const magnifier = document.getElementById("magnifierWindow");
        const getCursorPos = (e) => { const rect = img.getBoundingClientRect(); return { x: e.clientX - rect.left, y: e.clientY - rect.top }; };
        const moveMagnifier = (e) => { const pos = getCursorPos(e); const bgX = -(pos.x / img.offsetWidth * img.naturalWidth * zoom - magnifier.offsetWidth / 2); const bgY = -(pos.y / img.offsetHeight * img.naturalHeight * zoom - magnifier.offsetHeight / 2); magnifier.style.backgroundPosition = `${bgX}px ${bgY}px`; };
        const showMagnifier = (e) => { magnifier.style.backgroundImage = `url('${img.src}')`; magnifier.style.backgroundSize = `${img.naturalWidth * zoom}px ${img.naturalHeight * zoom}px`; magnifier.style.display = 'block'; moveMagnifier(e); };
        const hideMagnifier = () => { magnifier.style.display = 'none'; };
        containerEl.addEventListener("mouseenter", showMagnifier); containerEl.addEventListener("mousemove", moveMagnifier); containerEl.addEventListener("mouseleave", hideMagnifier);
        containerEl.magnifierInstance = { destroy: () => { containerEl.removeEventListener("mouseenter", showMagnifier); containerEl.removeEventListener("mousemove", moveMagnifier); containerEl.removeEventListener("mouseleave", hideMagnifier); magnifier.style.display = 'none'; } };
    }

    function viewActivityDetail(dataId) {
        $.getJSON(`<?= base_url('inbox/getCompleteActivityDetails/') ?>${dataId}`, function (response) {
            if (response.success) {
                const data = response.data;
                $('#verify_data_id').val(dataId);
                $('#verify_checksheet_id').val(data.checksheet_id);

                $('#verifyBuilding').text(data.building_name || '---');
                $('#verifyArea').text(data.area_name || '---');
                $('#verifyItem').text(data.item_name || '---');
                $('#verifyFindings').text(data.findings || '---');
                $('#verifyActionDescription').text(data.action_description || '---');

                const findContainer = $('#verifyFindingImageContainer');
                const findContainerEl = findContainer.get(0);
                if (findContainerEl && findContainerEl.magnifierInstance) findContainerEl.magnifierInstance.destroy();
                if (data.finding_image) {
                    const img = new Image();
                    img.onload = () => { findContainer.empty().append(img); createMagnifier(findContainerEl, 1.5); };
                    img.src = data.finding_image;
                } else { findContainer.html('<p class="text-muted small p-2">No Finding Image</p>'); }

                const actionContainer = $('#verifyActionImageContainer');
                const actionContainerEl = actionContainer.get(0);
                if (actionContainerEl && actionContainerEl.magnifierInstance) actionContainerEl.magnifierInstance.destroy();
                if (data.action_image) {
                    const img = new Image();
                    img.onload = () => { actionContainer.empty().append(img); createMagnifier(actionContainerEl, 1.5); };
                    img.src = data.action_image;
                } else { actionContainer.html('<p class="text-muted small p-2">No Action Image</p>'); }

                $('#activityDetailModal').modal('show');
            } else { Swal.fire('Error!', response.message || 'Could not load activity details.', 'error'); }
        }).fail(() => Swal.fire('Error!', 'An error occurred while fetching details.', 'error'));
    }

    function addRow(items = [], dris = []) {
        const rowsContainer = $('#findingsRowsContainer');
        let itemOptions = items.map(item => `<option value="${item.item_id}" data-control="${item.control || ''}">${item.item_name}</option>`).join('');

        let driOptions = '';
        if (dris && dris.length > 0) {
            driOptions = dris.map(dri => `<option value="${dri.dri_id}">${dri.fullname}</option>`).join('');
        } else {
            driOptions = `<option value="" disabled>No DRIs found for this area</option>`;
        }

        const newRowHtml = `<tr class="row-item">
            <td><select class="form-select form-select-sm item-select" name="item_id[]" required><option value="" selected disabled>Choose Item...</option>${itemOptions}</select><input type="hidden" class="control-hidden-field" name="control[]" value=""></td>
            <td><input type="text" class="form-control form-control-sm sub-control-field" name="sub_control[]" placeholder="Sub-control" readonly></td>
            <td><div class="form-check form-switch d-flex justify-content-center"><input class="form-check-input judgement-toggle" type="checkbox" role="switch" checked><label class="form-check-label ms-2 text-success">OK</label><input type="hidden" name="status[]" value="1"></div></td>
            <td><select class="form-select form-select-sm findings-select" name="findings_id[]" disabled><option value="">Choose Finding...</option></select></td>
            <td><div class="image-upload-container"><label class="btn btn-sm btn-outline-secondary file-input-label w-100"><i class='bx bx-paperclip'></i> Attach<input type="file" class="finding-image-input" name="finding_image[]" accept="image/png, image/jpeg, image/jpg" capture="environment" style="display: none;"></label><div class="image-preview-container align-items-center" style="display: none;"><img src="" alt="Preview" class="img-thumbnail me-2" style="max-width: 80px; max-height: 40px;"><button type="button" class="btn btn-danger btn-sm remove-image-btn">&times;</button></div></div></td>
            <td><select class="form-select form-select-sm" name="dri_id[]" required><option value="" selected disabled>Choose DRI...</option>${driOptions}</select></td>
            <td><input type="text" class="form-control form-control-sm" name="remarks[]" placeholder="Remarks..."></td>
            <td><div class="form-check form-switch d-flex justify-content-center"><input class="form-check-input priority-toggle" type="checkbox" role="switch"><input type="hidden" name="priority[]" value="0"></div></td>
            <td class="text-end"><button type="button" class="btn btn-danger btn-sm remove-row-btn"><i class='bx bx-trash'></i></button></td>
        </tr>`;
        const newRow = $(newRowHtml).appendTo(rowsContainer);
        newRow.find('.judgement-toggle').trigger('change');
    }

    function viewRequest(id) {
        $.getJSON(`<?= base_url('checksheet/getDropdownData') ?>/${id}`, function (response) {
            if (response.status !== 'success') { Swal.fire('Error!', 'Could not load data for the checksheet.', 'error'); return; }
            const items = response.data.items, dris = response.data.dris, areaName = response.data.area_name;
            const detailsContainer = $('#checksheetDetailsContainer'); detailsContainer.data('area-name', areaName); detailsContainer.empty();
            const formHtml = `<div class="table-responsive"><table class="table table-sm"><thead><tr><th style="width: 15%;">Item</th><th style="width: 15%;">Sub-control</th><th style="width: 10%;">Judgement</th><th style="width: 15%;">Findings</th><th style="width: 10%;">Image</th><th style="width: 15%;">DRI</th><th style="width: 15%;">Remarks</th><th style="width: 5%;">Priority</th><th style="width: 5%;"></th></tr></thead><tbody id="findingsRowsContainer"></tbody></table></div><div class="mt-2"><button type="button" class="btn btn-success btn-sm" id="addRowBtn"><i class='bx bx-plus'></i> Add Row</button></div>`;
            detailsContainer.html(formHtml); addRow(items, dris);
            detailsContainer.off('click', '#addRowBtn').on('click', '#addRowBtn', function () {
                const lastRow = $('#findingsRowsContainer').find('tr.row-item:last');
                if (lastRow.length > 0 && !lastRow.find('.item-select').val()) {
                    Swal.fire({ icon: 'warning', title: 'Cannot Add Row', text: 'Please select an item in the current row before adding a new one.', customClass: { container: 'swal-topmost' } });
                    return;
                }
                addRow(items, dris);
            });
            $('#modalTitle').text(`Checksheet Form - ${areaName}`); $('#checksheet_id').val(id); $('#checksheetModal').modal('show');
        }).fail(() => Swal.fire('Error!', 'Failed to fetch initial checksheet data.', 'error'));
    }

    function addFeedback(dataId) {
        const reviewModalEl = document.getElementById('reviewModal');
        const reviewModal = bootstrap.Modal.getInstance(reviewModalEl);

        reviewModalEl.addEventListener('hidden.bs.modal', () => {
            Swal.fire({
                title: 'Submit Feedback',
                input: 'textarea',
                inputLabel: 'Provide your feedback for this item',
                inputPlaceholder: 'Type your feedback here...',
                showCancelButton: true,
                confirmButtonText: 'Submit',

                preConfirm: (feedback) => {
                    if (!feedback) {
                        Swal.showValidationMessage(`Feedback cannot be empty`);
                    }
                    return feedback;
                }
            }).then((result) => {

                reviewModal.show();

                if (result.isConfirmed) {
                    const feedbackText = result.value;
                    $.ajax({
                        url: '<?= base_url('checksheet/submitFeedback') ?>',
                        type: 'POST',
                        data: {
                            data_id: dataId,
                            feedback: feedbackText,
                            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                        },
                        dataType: 'json',
                        success: function (res) {
                            if (res.status === 'success') {

                                $(`#feedback-cell-${dataId}`).text(feedbackText);

                                const Toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                    customClass: {
                                        container: 'swal2-toast-container'
                                    }
                                });
                                Toast.fire({
                                    icon: 'success',
                                    title: 'Feedback submitted!'
                                });
                            } else {
                                Swal.fire('Error!', res.message || 'An error occurred.', 'error');
                            }
                        },
                        error: function () {
                            Swal.fire('Request Failed!', 'An unknown error occurred.', 'error');
                        }
                    });
                }
            });
        }, { once: true });

        if (reviewModal) {
            reviewModal.hide();
        }
    }

    function reviewRequest(id) {
        $.getJSON(`<?= base_url('checksheet/getChecksheetReviewData') ?>/${id}`, function (response) {
            if (response.status !== 'success') { Swal.fire('Error!', response.message || 'Could not load review data.', 'error'); return; }

            const info = response.data.info, items = response.data.items;
            $('#reviewModalTitle').text(`Review Checksheet #${info.checksheet_id}`);
            $('#review_checksheet_id').val(info.checksheet_id);
            $('#reviewBuilding').text(info.building_name);
            $('#reviewArea').text(info.area_name);

            const itemsContainer = $('#reviewItemsContainer');
            itemsContainer.empty();

            let hasVerifiableItems = false;

            if (items.length > 0) {
                items.forEach(item => {
                    if (item.status == 1) {
                        hasVerifiableItems = true;
                    }

                    let judgementBadge;
                    if (item.status == 0) { let badgeText = item.priority == 1 ? 'NG ⚠️' : 'NG'; judgementBadge = `<span class="badge bg-danger">${badgeText}</span>`; }
                    else if (item.status == 2) { judgementBadge = '<span class="badge bg-success">OK</span>'; }
                    else if (item.status == 3) { judgementBadge = '<span class="badge bg-danger">NG HOLD</span>'; }
                    else if (item.status == 1) { judgementBadge = '<span class="badge bg-primary">FOR VERIFICATION</span>'; }
                    else { judgementBadge = '<span class="badge bg-secondary">---</span>'; }

                    const findingImageHtml = item.finding_image ? `<a href="${item.finding_image}" target="_blank"><img src="${item.finding_image}" class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;"></a>` : '---';
                    const actionImageHtml = item.action_image ? `<a href="${item.action_image}" target="_blank"><img src="${item.action_image}" class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;"></a>` : '---';

                    const checkboxHtml = (item.status == 1)
                        ? `<input class="form-check-input item-approve-check" type="checkbox" value="${item.data_id}">`
                        : '';

                    const feedbackButtonHtml = ((item.status == 1 || item.status == 3) && item.feedback == null)
                        ? `<button class="btn btn-sm btn-outline-info" onclick="addFeedback(${item.data_id})">Feedback</button>`
                        : '---';

                    const rowHtml = `<tr>
                        <td>${item.item_name || '---'}</td>
                        <td><center>${judgementBadge}</center></td>
                        <td>${item.findings_list || '---'}</td>
                        <td>${item.dri_name || '---'}</td>
                        <td>${item.remarks || '---'}</td>
                        <td><center>${findingImageHtml}</center></td>
                        <td><center>${actionImageHtml}</center></td>
                        <td>${item.action_description || '---'}</td>
                        <td id="feedback-cell-${item.data_id}">${item.feedback || '---'}</td>
                        <td><center>${feedbackButtonHtml}</center></td>
                        <td class="text-center">${checkboxHtml}</td>
                    </tr>`;
                    itemsContainer.append(rowHtml);
                });
            } else {
                itemsContainer.html('<tr><td colspan="11" class="text-center">No items found for this checksheet.</td></tr>');
            }

            $('#approveSelectedBtn').prop('disabled', !hasVerifiableItems);

            $('#reviewModal').modal('show');
        }).fail(() => Swal.fire('Error!', 'Failed to fetch checksheet review data.', 'error'));
    }

    (function () {
        const urlParams = new URLSearchParams(window.location.search);
        const checksheetId = urlParams.get('id');
        if (checksheetId) {
            reviewRequest(checksheetId);
        }
    })();
</script>
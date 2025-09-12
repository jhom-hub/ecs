<style>
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
    #actionImageContainer.placeholder { cursor: pointer; transition: background-color 0.2s; }
    #actionImageContainer.placeholder:hover { background-color: #2a2a2a; }
    .image-viewport img { width: 100%; height: 100%; object-fit: contain; }
    .comparison-arrow { display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #6c757d; }
    #magnifierWindow {
        position: fixed; top: 20px; right: 20px; width: 400px; height: 400px;
        border: 3px solid #fff; box-shadow: 0 0 10px rgba(0,0,0,0.5);
        background-repeat: no-repeat; background-color: #000;
        display: none; z-index: 2000;
        /* Improve performance by letting the GPU handle transformations */
        will-change: background-position;
    }
</style>
<div id="magnifierWindow"></div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered table-hover nowrap w-100" id="CorrectiveActionTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Area</th>
                                <th>Building</th>
                                <th>Item</th>
                                <th>Findings</th>
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
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Checksheets Feedback</h5>
                <div class="input-group mb-3"><input type="text" id="activitySearchInput" class="form-control form-control-sm" placeholder="Search..."><button class="btn btn-outline-secondary btn-sm" type="button" id="activitySearchBtn"><i class='bx bx-search'></i></button></div>
                <div id="checksheetFeedbackContainer" style="min-height: 350px; max-height: 350px; overflow-y: auto;"></div>
                <nav class="d-flex justify-content-between align-items-center mt-3"><button class="btn btn-secondary btn-sm" id="activityPrevBtn" disabled>Previous</button><span id="activityPageInfo" class="text-muted small"></span><button class="btn btn-secondary btn-sm" id="activityNextBtn" disabled>Next</button></nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalLabel">Take Corrective Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               <div class="row">
                <div class="col-12">
                    <h5 class="mb-3">Audit Details</h5>
                    <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <tbody>
                        <tr>
                            <th class="w-25">Building</th>
                            <td><span id="modalBuilding"></span></td>
                        </tr>
                        <tr>
                            <th>Area</th>
                            <td><span id="modalArea"></span></td>
                        </tr>
                        <tr>
                            <th>Item</th>
                            <td><span id="modalItem"></span></td>
                        </tr>
                        <tr>
                            <th>Sub Control</th>
                            <td><span id="modalSubControl"></span></td>
                        </tr>
                        <tr>
                            <th>Findings</th>
                            <td><span id="modalFindings"></span></td>
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
                                <div id="findingImageContainer" class="image-viewport"></div>
                            </div>
                            <div class="col-md-2 comparison-arrow d-none d-md-flex">&#10140;</div>
                            <div class="col-12 col-md-5 text-center">
                                <h6>Action Image Preview</h6>
                                <div id="actionImageContainer" class="image-viewport placeholder">
                                    <span class="text-muted" style="color: #ccc; font-size: 0.9rem;">Click to select an image</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <h5>Action Form</h5>
                        <form id="actionForm" enctype="multipart/form-data">
                            <input type="hidden" name="data_id" id="modalDataId">
                            <input type="hidden" name="item_id" id="modalItemId">
                            <input type="hidden" name="is_hold" id="isHold" value="0">
                            <input class="form-control" type="file" id="actionImage" name="action_image" accept="image/png, image/jpeg, image/jpg"  capture="environment" style="display: none;">
                            <div class="mb-3">
                                <label for="actionDescription" class="form-label">Action Description</label>
                                <textarea class="form-control" id="actionDescription" name="action_description" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="closureDate" class="form-label">Declared Date of Closure</label>
                                <div class="input-group">
                                    <div class="input-group-text">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="holdToggle">
                                        </div>
                                    </div>
                                    <input type="date" class="form-control" id="closureDate" name="closure_date" value="<?= date('Y-m-d'); ?>" disabled>
                                </div>
                                <div class="form-text">Toggle the switch to enable and set a future closure date.</div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" id="submitActionButton" form="actionForm" class="btn btn-primary">Submit Action</button>
            </div>
        </div>
    </div>
</div>

<script>
// ✅ NEW: Global variables for feedback pagination
var currentFeedbackPage = 1;
var currentFeedbackSearch = '';

function createMagnifier(containerEl, zoom) {
    const img = containerEl.querySelector('img');
    if (!img) return;

    const magnifier = document.getElementById("magnifierWindow");

    const getCursorPos = (e) => {
        const rect = img.getBoundingClientRect();
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
    };

    const moveMagnifier = (e) => {
        const pos = getCursorPos(e);
        const bgX = -(pos.x / img.offsetWidth * img.naturalWidth * zoom - magnifier.offsetWidth / 2);
        const bgY = -(pos.y / img.offsetHeight * img.naturalHeight * zoom - magnifier.offsetHeight / 2);
        magnifier.style.backgroundPosition = `${bgX}px ${bgY}px`;
    };

    const showMagnifier = (e) => {
        magnifier.style.backgroundImage = `url('${img.src}')`;
        magnifier.style.backgroundSize = `${img.naturalWidth * zoom}px ${img.naturalHeight * zoom}px`;
        magnifier.style.display = 'block';
        moveMagnifier(e);
    };
    
    const hideMagnifier = () => {
        magnifier.style.display = 'none';
    };
    
    containerEl.addEventListener("mouseenter", showMagnifier);
    containerEl.addEventListener("mousemove", moveMagnifier);
    containerEl.addEventListener("mouseleave", hideMagnifier);

    containerEl.magnifierInstance = {
        destroy: () => {
            containerEl.removeEventListener("mouseenter", showMagnifier);
            containerEl.removeEventListener("mousemove", moveMagnifier);
            containerEl.removeEventListener("mouseleave", hideMagnifier);
            magnifier.style.display = 'none';
        }
    };
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.round((now - date) / 1000);
    const minutes = Math.round(seconds / 60);
    const hours = Math.round(minutes / 60);
    const days = Math.round(hours / 24);
    if (seconds < 60) return `Just now`;
    if (minutes < 60) return `${minutes} min ago`;
    if (hours < 24) return `${hours} hr ago`;
    return `${days} days ago`;
}

function loadFeedbackActivity(page, search) {
    const container = $('#checksheetFeedbackContainer');
    const pageInfo = $('#activityPageInfo');
    const prevBtn = $('#activityPrevBtn');
    const nextBtn = $('#activityNextBtn');
    
    container.html('<div class="text-center p-5"><span class="spinner-border spinner-border-sm"></span> Loading...</div>');
    pageInfo.text('');
    prevBtn.prop('disabled', true);
    nextBtn.prop('disabled', true);

    $.getJSON('<?= base_url('corrective_action/getFeedbackActivity') ?>', { page: page, search: search }, function(res) {
        container.empty();
        if (res.status === 'success' && res.data.length > 0) {
            res.data.forEach(function(item) {
                const itemHtml = `
                    <div class="card mb-2 shadow-sm">
                        <div class="card-body p-3">
                            <p class="card-text mb-1 small">
                                Feedback on <strong>${item.item_name || 'N/A'}</strong> in <strong>${item.area_name || 'N/A'}</strong>:
                            </p>
                            <p class="card-text text-muted fst-italic bg-light border-start border-primary border-3 ps-2 p-1 rounded">
                                "${item.feedback}"
                            </p>
                            <div class="d-flex justify-content-end">
                                <small class="text-primary">${timeAgo(item.updated_at)}</small>
                            </div>
                        </div>
                    </div>`;
                container.append(itemHtml);
            });
            const pg = res.pagination;
            pageInfo.text(`Page ${pg.currentPage} of ${pg.totalPages}`);
            prevBtn.prop('disabled', pg.currentPage <= 1);
            nextBtn.prop('disabled', pg.currentPage >= pg.totalPages);
        } else {
            container.html('<p class="text-center text-muted p-5">No feedback found.</p>');
            pageInfo.text('Page 0 of 0');
        }
    }).fail(function() {
        container.html('<p class="text-center text-danger p-5">Failed to load feedback.</p>');
    });
}


$(document).ready(function () {
    const checksheetTable = $('#CorrectiveActionTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { 
            url: '<?= base_url('corrective_action/getPending') ?>', 
            type: 'POST' 
        },
        responsive: { details: { type: 'column', target: 'tr' } },
        columns: [
            { data: 'checksheet_id' }, 
            { data: 'area_name' }, 
            { data: 'building_name' },
            { data: 'item_name' }, 
            { data: 'findings' }, 
            { data: 'status' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        columnDefs: [
            { className: 'dtr-control', orderable: false, targets: 0 },
            {
                targets: 5,
                render: function (data, type, row) {
                    let statusBadge = '';
                    if (row.status == 0) {
                        statusBadge = '<span class="badge bg-danger">NG</span>';
                    } else if (row.status == 3) {
                        statusBadge = '<span class="badge bg-warning text-dark">NG HOLD</span>';
                    } else if (row.status == 1) {
                        statusBadge = '<span class="badge bg-primary">FOR VERIFICATION</span>';
                    } else {
                        statusBadge = '<span class="badge bg-secondary">N/A</span>';
                    }

                    let priorityBadge = '';
                    if (row.priority == 1) {
                        priorityBadge = ' <span class="badge bg-danger">⚠️</span>';
                    }
                    
                    return statusBadge + priorityBadge;
                }
            },
            { 
                targets: 6, 
                render: function(data, type, row) {
                    return `<button type="button" class="btn btn-sm btn-primary" onclick="reviewRequest(${row.data_id}, ${row.item_id})">Take an Action</button>`;
                } 
            }
        ]
    });

    $('#actionForm').on('submit', function(e) {
        e.preventDefault();
        // ✅ UPDATED: Select the button by its new, unique ID
        const $btn = $('#submitActionButton');
        const formData = new FormData(this);

        // This will now work correctly
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Submitting...');

        $.ajax({
            url: '<?= base_url('corrective_action/submitAction') ?>', type: 'POST',
            data: formData, processData: false, contentType: false, dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#actionModal').modal('hide');
                    Swal.fire('Success!', response.message, 'success');
                    checksheetTable.ajax.reload();
                } else { 
                    Swal.fire('Error!', response.message || 'An error occurred.', 'error');
                }
            },
            error: () => Swal.fire('Error!', 'An unexpected error occurred.', 'error'),
            // ✅ UPDATED: Reverted to a standard "Submit" text on completion
            complete: () => $btn.prop('disabled', false).text('Submit Action')
        });
    });

    $('#actionImageContainer').on('click', () => $('#actionImage').click());

    $('#actionImage').on('change', function() {
        const file = this.files[0];
        const imageContainer = $('#actionImageContainer');
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = () => {
                    imageContainer.empty().append(img).removeClass('placeholder');
                    if (imageContainer.get(0).magnifierInstance) {
                        imageContainer.get(0).magnifierInstance.destroy();
                    }
                    createMagnifier(imageContainer.get(0), 1.5);
                };
                img.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
    
    $('#holdToggle').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('#closureDate').prop('disabled', !isChecked);
        $('#isHold').val(isChecked ? '1' : '0');
    });

    $('#actionModal').on('hidden.bs.modal', function () {
        $('#actionForm')[0].reset();
        $('#holdToggle').prop('checked', false).prop('disabled', false).trigger('change');
        
        const findingContainer = document.getElementById('findingImageContainer');
        const actionContainer = document.getElementById('actionImageContainer');

        if (findingContainer.magnifierInstance) findingContainer.magnifierInstance.destroy();
        if (actionContainer.magnifierInstance) actionContainer.magnifierInstance.destroy();
        
        $(findingContainer).empty();
        $(actionContainer).addClass('placeholder').html('<span class="text-muted" style="color: #ccc; font-size: 0.9rem;">Click to select an image</span>');
    });

    loadFeedbackActivity(currentFeedbackPage, currentFeedbackSearch);

    $('#activitySearchBtn').on('click', function() {
        currentFeedbackSearch = $('#activitySearchInput').val();
        currentFeedbackPage = 1;
        loadFeedbackActivity(currentFeedbackPage, currentFeedbackSearch);
    });

    $('#activitySearchInput').on('keyup', function(e) {
        if (e.key === 'Enter') {
            $('#activitySearchBtn').click();
        }
    });

    $('#activityPrevBtn').on('click', function() {
        if (currentFeedbackPage > 1) {
            currentFeedbackPage--;
            loadFeedbackActivity(currentFeedbackPage, currentFeedbackSearch);
        }
    });

    $('#activityNextBtn').on('click', function() {
        $(this).prop('disabled', true);
        currentFeedbackPage++;
        loadFeedbackActivity(currentFeedbackPage, currentFeedbackSearch);
    });
});

function reviewRequest(dataId, itemId) {
    $.ajax({
        url: `<?= base_url('corrective_action/getItemDetails/') ?>${dataId}/${itemId}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                $('#modalBuilding').text(data.building_name);
                $('#modalArea').text(data.area_name);
                $('#modalItem').text(data.item_name);
                $('#modalSubControl').text(data.sub_control || 'N/A');
                $('#modalFindings').text(data.findings || 'N/A');

                $('#modalDataId').val(dataId);
                $('#modalItemId').val(itemId);

                $('#feedbackRow').remove();

                if (data.status == 3) {
                    if (data.action_description) {
                        $('#actionDescription').val(data.action_description);
                    }
                    if (data.declared_closure_date) {
                        $('#closureDate').val(data.declared_closure_date);
                        $('#holdToggle').prop('checked', true).prop('disabled', true);
                        $('#closureDate').prop('disabled', true);
                        $('#isHold').val('1');
                    }
                }

                if (data.status == 1 && data.feedback) {
                    const feedbackHtml = `<tr id="feedbackRow">
                                            <th class="w-25 table-warning">Auditor Feedback</th>
                                            <td colspan="3" class="table-warning">
                                                <span class="text-danger fst-italic">${data.feedback}</span>
                                            </td>
                                          </tr>`;
                    $('#modalFindings').closest('tr').after(feedbackHtml);

                    if (data.action_description) {
                        $('#actionDescription').val(data.action_description);
                    }

                    const actionImageContainer = $('#actionImageContainer');
                    if (data.action_image) {
                        const img = new Image();
                        img.onload = function() {
                            actionImageContainer.empty().append(img).removeClass('placeholder');
                            if (actionImageContainer.get(0).magnifierInstance) {
                                actionImageContainer.get(0).magnifierInstance.destroy();
                            }
                            createMagnifier(actionImageContainer.get(0), 1.5);
                        };
                        img.src = data.action_image;
                    }
                    
                    if (data.declared_closure_date) {
                        $('#closureDate').val(data.declared_closure_date);
                        $('#holdToggle').prop('checked', true).prop('disabled', true);
                        $('#closureDate').prop('disabled', true);
                        $('#isHold').val('1');
                    }
                }


                const imageContainer = $('#findingImageContainer');
                if (data.finding_image) {
                    const img = new Image();
                    img.onload = function() {
                        imageContainer.empty().append(img);
                        if (imageContainer.get(0).magnifierInstance) {
                            imageContainer.get(0).magnifierInstance.destroy();
                        }
                        createMagnifier(imageContainer.get(0), 1.5);
                    };
                    img.src = data.finding_image;
                } else {
                    imageContainer.html('<p class="text-muted">No image provided.</p>');
                }

                $('#actionModal').modal('show');
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: () => {
            Swal.fire('Error!', 'Failed to load details.', 'error');
        }
    });
}
</script>
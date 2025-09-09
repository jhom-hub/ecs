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
                <button type="submit" form="actionForm" class="btn btn-primary">Submit Action</button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * --- OPTIMIZED MAGNIFIER FUNCTION ---
 * The lag is fixed by separating event listeners.
 * `mouseenter`: Sets the background image and size ONCE.
 * `mousemove`: EFFICIENTLY updates only the background position.
 * `mouseleave`: Hides the magnifier.
 * This prevents costly style recalculations on every mouse movement.
 */
function createMagnifier(containerEl, zoom) {
    const img = containerEl.querySelector('img');
    if (!img) return;

    const magnifier = document.getElementById("magnifierWindow");

    const getCursorPos = (e) => {
        const rect = img.getBoundingClientRect();
        // Using clientX/Y is more reliable with getBoundingClientRect()
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
        moveMagnifier(e); // Set initial position
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
                    } else {
                        statusBadge = '<span class="badge bg-secondary">N/A</span>';
                    }

                    let priorityBadge = '';
                    if (row.priority == 1) {
                        priorityBadge = ' <span class="badge bg-danger">HIGH PRIORITY</span>';
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
        const $btn = $(this).find('button[type="submit"]');
        const formData = new FormData(this);
        
        $btn.prop('disabled', true).text('Submitting...');
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
                    // Ensure any previous magnifier instance is destroyed before creating a new one
                    if (imageContainer.get(0).magnifierInstance) {
                        imageContainer.get(0).magnifierInstance.destroy();
                    }
                    createMagnifier(imageContainer.get(0), 0.5);
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

        // Destroy magnifier instances to prevent memory leaks
        if (findingContainer.magnifierInstance) findingContainer.magnifierInstance.destroy();
        if (actionContainer.magnifierInstance) actionContainer.magnifierInstance.destroy();
        
        $(findingContainer).empty();
        $(actionContainer).addClass('placeholder').html('<span class="text-muted" style="color: #ccc; font-size: 0.9rem;">Click to select an image</span>');
    });
});

function reviewRequest(dataId, itemId) {
    $.ajax({
        url: `<?= base_url('corrective_action/getItemDetails/') ?>${dataId}/${itemId}`,
        type: 'GET', dataType: 'json',
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

                // Logic for previously held items
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

                const imageContainer = $('#findingImageContainer');
                if (data.finding_image) {
                    const img = new Image();
                    img.onload = function() {
                        imageContainer.empty().append(img);
                        // Ensure previous instance is destroyed
                        if (imageContainer.get(0).magnifierInstance) {
                            imageContainer.get(0).magnifierInstance.destroy();
                        }
                        createMagnifier(imageContainer.get(0), 0.5);
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
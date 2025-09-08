<style>
    /* A fixed-size viewport for both images */
    .image-viewport {
        position: relative;
        width: 100%;
        max-width: 350px; /* Max width for responsiveness */
        height: 260px;    /* Fixed height to maintain aspect */
        border: 1px solid #dee2e6;
        background-color: #000; /* Black background for letterboxing */
        margin: 0 auto; /* Center the box in its column */
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden; /* Hide magnifier overflow */
    }

    #actionImageContainer.placeholder {
        cursor: pointer;
        transition: background-color 0.2s;
    }

    #actionImageContainer.placeholder:hover {
        background-color: #2a2a2a; /* Darker background on hover */
    }

    .image-viewport img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .img-magnifier-glass {
        position: absolute;
        border: 3px solid #fff;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
        border-radius: 50%;
        cursor: none;
        width: 150px;
        height: 150px;
        display: none;
        z-index: 1090;
        background-repeat: no-repeat;
        pointer-events: none;
    }
    
    .comparison-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #6c757d;
    }
</style>

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
                                <div id="findingImageContainer" class="image-viewport">
                                    </div>
                            </div>

                            <div class="col-md-2 comparison-arrow d-none d-md-flex">
                                &#10140;
                            </div>
                            
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
                            <div class="mb-3">
                                <label for="actionDescription" class="form-label">Action Description</label>
                                <textarea class="form-control" id="actionDescription" name="action_description" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="actionImage" class="form-label">Upload Action Image</label>
                                <input class="form-control" type="file" id="actionImage" name="action_image" accept="image/*" capture="environment"  required style="display: block;">
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
    function createMagnifier(containerEl, zoom) {
        const img = containerEl.querySelector('img');
        if (!img) return;

        if (containerEl.magnifierInstance) {
            containerEl.magnifierInstance.destroy();
        }

        const glass = document.createElement("DIV");
        glass.className = 'img-magnifier-glass';
        containerEl.appendChild(glass);

        glass.style.backgroundImage = "url('" + img.src + "')";
        glass.style.backgroundRepeat = "no-repeat";
        glass.style.backgroundSize = (img.naturalWidth * zoom) + "px " + (img.naturalHeight * zoom) + "px";

        const glassWidth = glass.offsetWidth / 2;

        const moveMagnifier = (e) => {
            e.preventDefault();
            const pos = getCursorPos(e);
            let x = pos.x;
            let y = pos.y;

            const imgRect = img.getBoundingClientRect();
            const containerRect = containerEl.getBoundingClientRect();
            
            const imgLeft = imgRect.left - containerRect.left;
            const imgTop = imgRect.top - containerRect.top;

            if (x < imgLeft) x = imgLeft;
            if (x > imgLeft + img.offsetWidth) x = imgLeft + img.offsetWidth;
            if (y < imgTop) y = imgTop;
            if (y > imgTop + img.offsetHeight) y = imgTop + img.offsetHeight;

            glass.style.left = (x - glassWidth) + "px";
            glass.style.top = (y - glassWidth) + "px";

            const bgX = ((x - imgLeft) / img.offsetWidth * img.naturalWidth * zoom) - glassWidth;
            const bgY = ((y - imgTop) / img.offsetHeight * img.naturalHeight * zoom) - glassWidth;

            glass.style.backgroundPosition = `-${bgX}px -${bgY}px`;
        };
        
        const getCursorPos = (e) => {
            const a = containerEl.getBoundingClientRect();
            return {
                x: e.pageX - a.left - window.scrollX,
                y: e.pageY - a.top - window.scrollY
            };
        };
        
        const showMagnifier = () => { glass.style.display = 'block'; };
        const hideMagnifier = () => { glass.style.display = 'none'; };

        containerEl.addEventListener("mousemove", moveMagnifier);
        containerEl.addEventListener("mouseenter", showMagnifier);
        containerEl.addEventListener("mouseleave", hideMagnifier);

        const destroy = () => {
            containerEl.removeEventListener("mousemove", moveMagnifier);
            containerEl.removeEventListener("mouseenter", showMagnifier);
            containerEl.removeEventListener("mouseleave", hideMagnifier);
            if (glass.parentElement) {
                glass.parentElement.removeChild(glass);
            }
        };

        containerEl.magnifierInstance = { destroy };
    }


    $(document).ready(function () {
        const checksheetTable = $('#CorrectiveActionTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: { 
                url: '<?= base_url('corrective_action/getPending') ?>', 
                type: 'POST' 
            },
            responsive: {
                details: {
                    type: 'column',
                    target: 'tr'
                }
            },
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
                {
                    className: 'dtr-control',
                    orderable: false,
                    targets: 0
                },
                { 
                    targets: 5, 
                    render: function(data, type, row) {
                        if (row.priority == 1) {
                            return '<span class="badge bg-danger">HIGH PRIORITY</span>';
                        } else {
                            return '<span class="badge bg-danger">NG</span>';
                        }
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
            $.ajax({
                url: '<?= base_url('corrective_action/submitAction') ?>', type: 'POST',
                data: new FormData(this), processData: false, contentType: false, dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#actionModal').modal('hide');
                        Swal.fire('Success!', response.message, 'success');
                        checksheetTable.ajax.reload();
                    } else { 
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: () => {
                    Swal.fire('Error!', 'An unexpected error occurred.', 'error');
                }
            });
        });

        $('#actionImageContainer').on('click', function() {
            if ($(this).hasClass('placeholder')) {
                $('#actionImage').click();
            }
        });

        $('#actionImage').on('change', function() {
            const file = this.files[0];
            const imageContainer = $('#actionImageContainer');
            imageContainer.html('<span class="text-muted" style="color: #ccc;">Loading...</span>');
            
            imageContainer.removeClass('placeholder');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        imageContainer.empty().append(img);
                        createMagnifier(imageContainer.get(0), 1.5);
                    };
                    img.id = 'modalActionImage';
                    img.src = e.target.result;
                }
                reader.readAsDataURL(file);
            } else {
                imageContainer.addClass('placeholder');
                imageContainer.html('<span class="text-muted" style="color: #ccc; font-size: 0.9rem;">Click to select an image</span>');
            }
        });

        $('#actionModal').on('hidden.bs.modal', function () {
            $('#actionForm')[0].reset();

            const findingContainer = document.getElementById('findingImageContainer');
            const actionContainer = document.getElementById('actionImageContainer');

            if (findingContainer && findingContainer.magnifierInstance) {
                findingContainer.magnifierInstance.destroy();
            }
            if (actionContainer && actionContainer.magnifierInstance) {
                actionContainer.magnifierInstance.destroy();
            }
            
            $(findingContainer).empty();
            $(actionContainer)
                .addClass('placeholder')
                .html('<span class="text-muted" style="color: #ccc; font-size: 0.9rem;">Click to select an image</span>');
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

                    const imageContainer = $('#findingImageContainer');
                    imageContainer.html('<span class="text-muted" style="color: #ccc;">Loading...</span>');

                    if (data.finding_image) {
                        const img = new Image();
                        img.onload = function() {
                            imageContainer.empty().append(img);
                            createMagnifier(imageContainer.get(0), 1.5);
                        };
                        img.id = 'modalFindingImage';
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
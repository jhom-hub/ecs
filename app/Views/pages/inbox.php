<style>
    .activity-link {
        text-decoration: none;
        color: inherit;
    }
    .activity-link:hover .messages {
        background-color: #f8f9fa; /* Optional: Add a subtle hover effect */
        cursor: pointer;
    }
</style>

<div class="row">
    <div class="col-lg-6 inboxContainer overflow-hidden mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Checksheet Activity</h5>
                
                <div class="input-group mb-3">
                    <input type="text" id="activitySearchInput" class="form-control form-control-sm" placeholder="Search item, area, department...">
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="activitySearchBtn">
                        <i class='bx bx-search'></i>
                    </button>
                </div>

                <div id="checksheetActivityContainer" style="min-height: 350px; max-height: 350px; overflow-y: auto;"></div>

                <nav class="d-flex justify-content-between align-items-center mt-3">
                    <button class="btn btn-secondary btn-sm" id="activityPrevBtn" disabled>Previous</button>
                    <span id="activityPageInfo" class="text-muted small"></span>
                    <button class="btn btn-secondary btn-sm" id="activityNextBtn" disabled>Next</button>
                </nav>
            </div>
        </div>
    </div>
    <div class="col-lg-6 overflow-hidden">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Incoming Item Request</h5>
                <div id="requestItemsContainer" style="min-height: 440px; max-height: 500px; overflow-y: auto;"></div>
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
        // Initial load for both components
        loadChecksheetActivity(currentActivityPage, currentActivitySearch);
        loadRequestItems();

        // --- Event Listeners for Custom Component ---
        $('#activitySearchBtn').on('click', function() {
            currentActivitySearch = $('#activitySearchInput').val();
            currentActivityPage = 1; // Reset to first page on new search
            loadChecksheetActivity(currentActivityPage, currentActivitySearch);
        });
        $('#activitySearchInput').on('keyup', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                $('#activitySearchBtn').click();
            }
        });
        $('#activityPrevBtn').on('click', function() {
            if (currentActivityPage > 1) {
                currentActivityPage--;
                loadChecksheetActivity(currentActivityPage, currentActivitySearch);
            }
        });
        $('#activityNextBtn').on('click', function() {
            $(this).prop('disabled', true); // Prevent double-clicking
            currentActivityPage++;
            loadChecksheetActivity(currentActivityPage, currentActivitySearch);
        });
    });

    /**
     * Helper function to format date strings into a "time ago" format.
     */
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

    /**
     * Fetches and displays a paginated, searchable list of checksheet activities.
     */
    function loadChecksheetActivity(page, search) {
    const container = $('#checksheetActivityContainer');
    const pageInfo = $('#activityPageInfo');
    const prevBtn = $('#activityPrevBtn');
    const nextBtn = $('#activityNextBtn');
    
    container.html('<div class="text-center p-5"><span class="spinner-border spinner-border-sm"></span> Loading...</div>');
    pageInfo.text('');
    prevBtn.prop('disabled', true);
    nextBtn.prop('disabled', true);

    $.ajax({
        url: '<?= base_url('inbox/getHoldActivity') ?>',
        type: 'GET',
        data: {
            page: page,
            search: search
        },
        dataType: 'json',
        success: function(response) {
            container.empty();
            
            if (response.status === 'success' && response.data.length > 0) {
                response.data.forEach(function(item) {
                    let statusText = (item.status == 0)
                        ? `<strong>${item.item_name}</strong> in <strong>${item.area_name}</strong> was put on HOLD.`
                        : `<strong>${item.item_name}</strong> in <strong>${item.area_name}</strong> was marked as DONE.`;

                    const activityHtml = `
                        <a href="http://10.216.15.10/ecs/checksheet" class="activity-link">
                            <div class="messages mb-1 p-3 shadow-sm rounded">
                                <p class="card-text mb-1">
                                    ${statusText}
                                </p>
                                <p class="card-text text-muted fst-italic mb-2">
                                    "${item.action_description}"
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">By: ${item.department_name || 'N/A'}</small>
                                    <small class="text-primary">${timeAgo(item.created_at)}</small>
                                </div>
                            </div>
                        </a>
                    `;
                    container.append(activityHtml);
                });

                const pagination = response.pagination;
                pageInfo.text(`Page ${pagination.currentPage} of ${pagination.totalPages}`);
                prevBtn.prop('disabled', pagination.currentPage <= 1);
                nextBtn.prop('disabled', pagination.currentPage >= pagination.totalPages);

            } else {
                container.html('<p class="text-center text-muted p-5">No matching activity found.</p>');
                pageInfo.text('Page 0 of 0');
            }
        },
        error: function() {
            container.html('<p class="text-center text-danger p-5">Failed to load activity.</p>');
        }
    });
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
                                <p class="card-text mb-1"><strong>Area: </strong>${item.area_name}</p>
                                <p class="card-text mb-1"><strong>Item: </strong>${item.item_name}</p>
                                <p class="card-text mb-2"><strong>Reason: </strong>${item.description}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="icons">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="approveRequest(${item.request_id})" title="Approve">
                                            <i class='bx bx-check'></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="rejectRequest(${item.request_id})" title="Reject">
                                            <i class='bx bx-x'></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">From: ${item.firstname} ${item.lastname}</small>
                                </div>
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
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>' 
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Approved!', res.message, 'success');
                            loadRequestItems(); 
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
                        status: 2, 
                        remarks: result.value, 
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>' 
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Rejected!', res.message, 'success');
                            loadRequestItems(); 
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

</script>
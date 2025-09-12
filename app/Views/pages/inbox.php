<div class="row">
    <div class="col-lg-12 overflow-hidden">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Incoming Item Request</h5>
                <div id="requestItemsContainer" style="min-height: 440px; max-height: 500px; overflow-y: auto;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="approveRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <form id="approveRequestForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Approve Item Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="approve_request_id" name="request_id">
          <input type="hidden" name="status" value="1">
          <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
          
          <div class="mb-2">
            <p class="mb-0"><strong>Item Name:</strong> <span id="approve_item_name"></span></p>
            <p class="mb-0"><strong>Area:</strong> <span id="approve_area_name"></span></p>
            <p class="mb-0"><strong>Building:</strong> <span id="approve_building_name"></span></p>
          </div>
          <hr>
          <h6>Add Findings for this Item</h6>
          <div class="mt-2">
            <div id="findings_list" class="findings-list mb-2"></div>
            <div class="input-group">
                <input type="text" class="form-control form-control-sm new-finding-input" placeholder="Type a finding and press Enter...">
            </div>
            <small class="form-text text-muted">You can add multiple potential findings for this new item.</small>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Approve and Save Findings</button>
      </div>
    </form>
  </div>
</div>

<script>
    if (typeof window.currentActivityPage === 'undefined') {
        var currentActivityPage = 1;
        var currentActivitySearch = '';
    }

    $(document).ready(function () {
        // --- Unchanged Functions ---
        loadChecksheetActivity(currentActivityPage, currentActivitySearch);
        loadRequestItems();
        $('#activitySearchBtn').on('click', function() {
            currentActivitySearch = $('#activitySearchInput').val();
            currentActivityPage = 1; 
            loadChecksheetActivity(currentActivityPage, currentActivitySearch);
        });
        $('#activitySearchInput').on('keyup', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) $('#activitySearchBtn').click();
        });
        $('#activityPrevBtn').on('click', function() {
            if (currentActivityPage > 1) {
                currentActivityPage--;
                loadChecksheetActivity(currentActivityPage, currentActivitySearch);
            }
        });
        $('#activityNextBtn').on('click', function() {
            $(this).prop('disabled', true);
            currentActivityPage++;
            loadChecksheetActivity(currentActivityPage, currentActivitySearch);
        });
        // --- End of Unchanged ---

        // ✅ NEW: Event handlers for the approval modal's findings input
        $('#approveRequestModal').on('keypress', '.new-finding-input', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                const findingName = $(this).val().trim();
                if (findingName) {
                    const findingTagHtml = `
                        <span class="badge bg-secondary me-1 mb-1 finding-tag">
                            ${findingName}
                            <input type="hidden" name="findings[]" value="${findingName}">
                            <a href="#" class="text-white ms-1 remove-finding-tag">&times;</a>
                        </span>`;
                    $('#findings_list').append(findingTagHtml);
                    $(this).val('');
                }
            }
        });

        $('#approveRequestModal').on('click', '.remove-finding-tag', function(e) {
            e.preventDefault();
            $(this).parent().remove();
        });

        // ✅ NEW: Form submission for the approval modal
        $('#approveRequestForm').submit(function(e) {
            e.preventDefault();
            const $btn = $(this).find('button[type="submit"]');
            $btn.prop('disabled', true).text('Approving...');
            $.ajax({
                url: '<?= base_url('inbox/updateRequestStatus') ?>',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        $('#approveRequestModal').modal('hide');
                        Swal.fire('Approved!', res.message, 'success');
                        loadRequestItems();
                    } else {
                        Swal.fire('Error!', res.message || 'Request failed.', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON ? xhr.responseJSON.message : 'An unknown error occurred.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Approve and Save Findings');
                }
            });
        });
    });
    
    function timeAgo(dateString) {
        const date = new Date(dateString); const now = new Date();
        const seconds = Math.round((now - date) / 1000); const minutes = Math.round(seconds / 60);
        const hours = Math.round(minutes / 60); const days = Math.round(hours / 24);
        if (seconds < 60) return `Just now`; if (minutes < 60) return `${minutes} min ago`;
        if (hours < 24) return `${hours} hr ago`; return `${days} days ago`;
    }
    function loadChecksheetActivity(page, search) {
        const c = $('#checksheetActivityContainer'), pI = $('#activityPageInfo'), pB = $('#activityPrevBtn'), nB = $('#activityNextBtn');
        c.html('<div class="text-center p-5"><span class="spinner-border spinner-border-sm"></span> Loading...</div>');
        pI.text(''); pB.prop('disabled', true); nB.prop('disabled', true);
        $.getJSON('<?= base_url('inbox/getHoldActivity') ?>', { page: page, search: search }, function(res) {
            c.empty();
            if (res.status === 'success' && res.data.length > 0) {
                res.data.forEach(function(item) {
                    let sT = (item.status==0)?`<strong>${item.item_name}</strong> in <strong>${item.area_name}</strong> was put on HOLD.`:`<strong>${item.item_name}</strong> in <strong>${item.area_name}</strong> was marked as DONE.`;
                    c.append(`<a href="http://10.216.15.10/ecs/checksheet" class="activity-link"><div class="messages mb-1 p-3 shadow-sm rounded"><p class="card-text mb-1">${sT}</p><p class="card-text text-muted fst-italic mb-2">"${item.action_description}"</p><div class="d-flex justify-content-between align-items-center"><small class="text-muted">By: ${item.department_name||'N/A'}</small><small class="text-primary">${timeAgo(item.created_at)}</small></div></div></a>`);
                });
                const pg = res.pagination; pI.text(`Page ${pg.currentPage} of ${pg.totalPages}`);
                pB.prop('disabled', pg.currentPage <= 1); nB.prop('disabled', pg.currentPage >= pg.totalPages);
            } else { c.html('<p class="text-center text-muted p-5">No matching activity found.</p>'); pI.text('Page 0 of 0'); }
        }).fail(function() { c.html('<p class="text-center text-danger p-5">Failed to load activity.</p>'); });
    }
    function loadRequestItems() {
        $.getJSON('<?= base_url('inbox/getPendingRequests') ?>', function(response) {
            const c = $('#requestItemsContainer'); c.empty();
            if (response.status === 'success' && response.data.length > 0) {
                response.data.forEach(function(item) {
                    c.append(`<div class="messages mb-1 p-3 shadow-sm rounded"><p class="card-text mb-1"><strong>Area: </strong>${item.area_name}</p><p class="card-text mb-1"><strong>Item: </strong>${item.item_name}</p><p class="card-text mb-2"><strong>Reason: </strong>${item.description}</p><div class="d-flex justify-content-between align-items-center"><div class="icons"><button type="button" class="btn btn-primary btn-sm" onclick="approveRequest(${item.request_id})" title="Approve"><i class='bx bx-check'></i></button><button type="button" class="btn btn-danger btn-sm" onclick="rejectRequest(${item.request_id})" title="Reject"><i class='bx bx-x'></i></button></div><small class="text-muted">From: ${item.firstname} ${item.lastname}</small></div></div>`);
                });
            } else { c.html('<p class="text-center text-muted p-3">No incoming item requests.</p>'); }
        }).fail(function() { $('#requestItemsContainer').html('<p class="text-center text-danger p-3">Failed to load requests.</p>'); });
    }
    function rejectRequest(id) {
        Swal.fire({title:'Reject Request',text:'Please provide a reason for rejection:',icon:'warning',input:'textarea',inputPlaceholder:'Type your reason here...',showCancelButton:true,confirmButtonColor:'#d33',cancelButtonColor:'#3085d6',confirmButtonText:'Yes, reject it!',inputValidator:(v)=>{if(!v)return 'You need to write a reason for rejection!'}}).then((r)=>{if(r.isConfirmed){$.post('<?= base_url('request-items/updateRequestStatus') ?>',{request_id:id,status:2,remarks:r.value,'<?= csrf_token() ?>':'<?= csrf_hash() ?>'},function(res){if(res.status==='success'){Swal.fire('Rejected!',res.message,'success');loadRequestItems();}else{Swal.fire('Error!',res.message||'Request failed.','error');}},'json').fail((xhr)=>{Swal.fire('Error!',xhr.responseJSON?xhr.responseJSON.message:'An unknown error occurred.','error');});}});
    }
    function approveRequest(id) {
        // Fetch request details to populate the modal
        $.getJSON(`<?= base_url('inbox/get-for-approval/') ?>${id}`, function(res) {
            if (res.status === 'success') {
                const item = res.data;
                // Populate modal fields
                $('#approve_request_id').val(item.request_id);
                $('#approve_item_name').text(item.item_name);
                $('#approve_area_name').text(item.area_name);
                $('#approve_building_name').text(item.building_name);

                // Reset findings
                $('#findings_list').empty();
                $('.new-finding-input').val('');

                // Show the modal
                $('#approveRequestModal').modal('show');
            } else {
                Swal.fire('Error!', res.message || 'Could not fetch request details.', 'error');
            }
        }).fail(function() {
            Swal.fire('Error!', 'An error occurred while fetching request details.', 'error');
        });
    }
</script>
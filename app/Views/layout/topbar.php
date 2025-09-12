<style>
    .dropdown-menu {
        z-index: 99999 !important;
    }
    .notification-item {
        white-space: normal; /* Allow text to wrap */
    }
    .notification-item p {
        margin-bottom: 0.25rem;
    }
    .notification-item small {
        font-size: 0.75rem;
    }
</style>

<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <ul class="navbar-nav flex-row align-items-center ms-auto">
             <?php if ($role === 'ADMINISTRATOR' || $role === 'GA'): ?>
            <li class="nav-item dropdown">
                <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
                    <i class='bx bxs-bell' style="font-size: 1.5rem;"></i>
                    <span class="badge bg-danger rounded-pill badge-notifications" style="font-size: 0.6rem; display: none;">0</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications" style="width: 350px;">
                    <li class="dropdown-header">
                        You have <span class="badge-notifications">0</span> new requests
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>

                    <div id="notificationItemsContainer" style="max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-item text-center text-muted p-3">Loading requests...</li>
                    </div>

                    <li class="dropdown-footer">
                        <div class="dropdown-divider"></div>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="images/icon.ico" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="images/icon.ico" alt
                                            class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block"><?= session()->get('fullname') ?? ''; ?></span>
                                    <small class="text-muted"><?= session()->get('role') ?? ''; ?></small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= base_url('auth/logout') ?>">
                            <i class="bx bx-power-off me-2 text-danger"></i>
                            <span class="align-middle text-danger">Log Out</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
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
          
          <div class="mb-3">
            <label for="approve_item_name_input" class="form-label"><strong>Item Name</strong></label>
            <input type="text" class="form-control" id="approve_item_name_input" name="item_name" required>
          </div>
          <div class="mb-2">
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
    $(document).ready(function () {
        loadNotifications();
        setInterval(loadNotifications, 1000);

        $('#approveRequestModal').on('keypress', '.new-finding-input', function(e) {
            if (e.which === 13) {
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
                        loadNotifications();
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

    function loadNotifications() {
        const container = $('#notificationItemsContainer');
        const badge = $('.badge-notifications');

        $.getJSON('<?= base_url('getPendingRequestCount') ?>', function(res) {
            if (res.status === 'success') {
                badge.text(res.count);
                $('.dropdown-header .badge-notifications').text(res.count);
                if (res.count > 0) {
                    badge.show();
                } else {
                    badge.hide();
                }
            }
        });
        
        $.getJSON('<?= base_url('inbox/getPendingRequests') ?>', function(response) {
            container.empty();
            if (response.status === 'success' && response.data.length > 0) {
                response.data.forEach(function(item) {
                    const itemHtml = `
                        <li class="notification-item dropdown-item">
                            <p class="mb-1">
                                <strong>${item.item_name}</strong>
                                <small class="text-muted d-block">${item.area_name}</small>
                            </p>
                            <p class="mb-2 fst-italic">"${item.description}"</p>
                            <small class="text-muted">From: ${item.firstname} ${item.lastname}</small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-primary" onclick="approveRequest(${item.request_id})">Approve</button>
                                <button class="btn btn-sm btn-danger" onclick="rejectRequest(${item.request_id})">Reject</button>
                            </div>
                        </li>
                        <li><div class="dropdown-divider"></div></li>`;
                    container.append(itemHtml);
                });
            } else {
                container.html('<li class="dropdown-item text-center text-muted p-3">No incoming item requests.</li>');
            }
        }).fail(function() {
            container.html('<li class="dropdown-item text-center text-danger p-3">Failed to load requests.</li>');
        });
    }

    function approveRequest(id) {
        $.getJSON(`<?= base_url('inbox/get-for-approval/') ?>${id}`, function(res) {
            if (res.status === 'success') {
                const item = res.data;
                $('#approve_request_id').val(item.request_id);
                $('#approve_item_name_input').val(item.item_name);
                $('#approve_area_name').text(item.area_name);
                $('#approve_building_name').text(item.building_name);
                $('#findings_list').empty();
                $('.new-finding-input').val('');
                $('#approveRequestModal').modal('show');
            } else {
                Swal.fire('Error!', res.message || 'Could not fetch request details.', 'error');
            }
        }).fail(function() {
            Swal.fire('Error!', 'An error occurred while fetching request details.', 'error');
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
                if (!value) return 'You need to write a reason for rejection!'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= base_url('inbox/updateRequestStatus') ?>', {
                    request_id: id,
                    status: 2,
                    remarks: result.value,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Rejected!', res.message, 'success');
                        loadNotifications();
                    } else {
                        Swal.fire('Error!', res.message || 'Request failed.', 'error');
                    }
                }, 'json').fail((xhr) => {
                    Swal.fire('Error!', xhr.responseJSON ? xhr.responseJSON.message : 'An unknown error occurred.', 'error');
                });
            }
        });
    }
</script>
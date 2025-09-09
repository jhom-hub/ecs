<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <img width="50" src="images/logo2.png" alt="">
        <a href="javascript:void(0);" class="app-brand-link load-page" data-page="dashboard" data-title="Dashboard">
            <span class="demo menu-text fw-bolder ms-2">CheckSheet</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>
    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item active">
            <a href="javascript:void(0);" class="menu-link load-page" data-page="dashboard" data-title="Dashboard">
                <i class="menu-icon tf-icons bx bx-home"></i>
                <div>Dashboard</div>
            </a>
        </li>
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link load-page" data-page="data_summary" data-title="Data Summary">
                <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                <div>Data Summary</div>
            </a>
        </li>

        <?php $role = session()->get('role'); ?>

        <?php if ($role === 'ADMINISTRATOR' || $role === 'GA' || $role === 'DRI' || $role === 'AUDITOR'): ?>
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Pages</span>
            </li>

            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link load-page" data-page="inbox" data-title="Inbox">
                    <i class="menu-icon tf-icons bx bx-envelope"></i>
                    <div>Inbox</div>
                </a>
            </li>

            <!-- Checksheet -->
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link load-page" data-page="checksheet" data-title="Submit Check Sheet">
                    <i class="menu-icon tf-icons bx bx-check-square"></i>
                    <div>Checksheet</div>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($role === 'ADMINISTRATOR' || $role === 'GA' || $role === 'DRI'): ?>
            <!-- Corrective Action -->
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link load-page" data-page="corrective_action" data-title="Corrective Action">
                    <i class="menu-icon tf-icons bx bx-edit-alt"></i>
                    <div>Corrective Action</div>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($role === 'ADMINISTRATOR' || $role === 'GA'): ?>
            <!-- Send Request -->
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link load-page" data-page="send_request" data-title="Send Request">
                    <i class="menu-icon tf-icons bx bx-paper-plane"></i>
                    <div>Send Request</div>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($role === 'ADMINISTRATOR' || $role === 'AUDITOR' || $role === 'GA'): ?>
            <!-- Audit Trail -->
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link load-page" data-page="audit_trail" data-title="Audit Trail">
                    <i class="menu-icon tf-icons bx bx-shield-quarter"></i>
                    <div>Audit Trail</div>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($role === 'ADMINISTRATOR'): ?>
            <!-- Maintenance -->
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-cog"></i>
                    <div>Maintenance</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item"><a href="javascript:void(0);" class="menu-link load-page" data-page="checksheet_maintenance" data-title="Checksheets"><i class="bx bx-list-check me-2"></i><div>Checksheets</div></a></li>
                    <li class="menu-item"><a href="javascript:void(0);" class="menu-link load-page" data-page="auditor_maintenance" data-title="Auditors"><i class="bx bx-user-voice me-2"></i><div>Auditors</div></a></li>
                    <li class="menu-item"><a href="javascript:void(0);" class="menu-link load-page" data-page="dri_maintenance" data-title="DRI"><i class="bx bx-id-card me-2"></i><div>DRI</div></a></li>
                    <li class="menu-item"><a href="javascript:void(0);" class="menu-link load-page" data-page="findings_maintenance" data-title="Findings"><i class="bx bx-search-alt me-2"></i><div>Findings</div></a></li>
                    <li class="menu-item"><a href="javascript:void(0);" class="menu-link load-page" data-page="building_maintenance" data-title="Building"><i class="bx bx-building me-2"></i><div>Building</div></a></li>
                    <li class="menu-item"><a href="javascript:void(0);" class="menu-link load-page" data-page="area_maintenance" data-title="Area"><i class="bx bx-map-pin me-2"></i><div>Area</div></a></li>
                    <li class="menu-item"><a href="javascript:void(0);" class="menu-link load-page" data-page="item_maintenance" data-title="Item"><i class="bx bx-box me-2"></i><div>Item</div></a></li>
                    <li class="menu-item"><a href="javascript:void(0);" class="menu-link load-page" data-page="department_maintenance" data-title="Department"><i class="bx bx-buildings me-2"></i><div>Department</div></a></li>
                    <li class="menu-item"><a href="javascript:void(0);" class="menu-link load-page" data-page="division_maintenance" data-title="Division"><i class="bx bx-git-branch me-2"></i><div>Division</div></a></li>
                    <li class="menu-item"><a href="javascript:void(0);" class="menu-link load-page" data-page="section_maintenance" data-title="Section"><i class="bx bx-grid-alt me-2"></i><div>Section</div></a></li>
                    <li class="menu-item"><a href="javascript:void(0);" class="menu-link load-page" data-page="users_maintenance" data-title="Users Management"><i class="bx bx-user-cog me-2"></i><div>Users Management</div></a></li>
                </ul>
            </li>
        <?php endif; ?>
    </ul>
</aside>

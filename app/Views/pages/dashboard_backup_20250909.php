<?php
$original_image_width = 1200;
$original_image_height = 800;
$user_role = session()->get('role') ?? '';
?>

<style>
    body {
        background: #f4f4f4;
    }

    .map-container {
        position: relative;
        width: 100%;
    }

    .map-container img {
        max-width: 100%;
        height: auto;
        display: block;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .marker {
        position: absolute;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        cursor: pointer;
        transform: translate(-50%, -50%);
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5);
    }

    .marker .ng {
        background-color: #dc3545;
        z-index: -10;
    }

    .pending,
    .ok {
        display: none;
    }

    .marker.ng::before,
    .marker.ng::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 50%;
        width: 10px;
        height: 10px;
        transform: translate(-50%, -50%);
        border-radius: 50%;
        background-color: #dc3545;
        z-index: -1;
        animation: sonar 1.5s ease-out infinite;
    }

    .marker.ng::after {
        animation-delay: 0.5s;
    }

    @keyframes sonar {
        0% {
            width: 100%;
            height: 100%;
            opacity: 0.6;
        }

        100% {
            width: 400%;
            height: 400%;
            opacity: 0;
        }
    }

    .coords {
        margin-top: 15px;
        padding: 10px;
        background: #fff;
        border: 1px solid #ccc;
        display: inline-block;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Styles for the new table */
    .findings-table {
        background-color: #e9ecef;
    }

    .findings-table thead th {
        background-color: #000;
        color: #fff;
        text-align: center;
        vertical-align: middle;
    }

    .findings-table tbody td {
        text-align: center;
        vertical-align: middle;
    }

    .findings-table tbody tr:last-child {
        font-weight: bold;
    }

    .tooltip-inner {
        max-width: 250px !important;
        /* Set the desired width */
        text-align: left;
        padding: 10px;
    }

    .tooltip-inner img {
        max-width: 100% !important;
        /* Make sure the image doesn't overflow the 250px width */
        height: auto;
        /* Maintain aspect ratio */
        display: block;
        /* Ensures it takes up full width available */
        margin-top: 5px;
        /* Add some space above the image */
        border-radius: 4px;
        /* Optional: subtle rounded corners for the image */
    }

    .hidden {
        display: none;
    }

    .bldng-name {
        margin-bottom: 20px !important;
    }

    .building_name {
        margin-bottom: -17px !important;
    }
</style>

<!-- The ApexCharts library is already loaded in your main scripts, so the CDN link here is removed. -->

<!-- TOP ROW: MAP AND STATS -->
<div class="tabs mb-1">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a href="#" class="nav-link active" aria-current="page" data-target=".level0">Level 0</a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" data-target=".level1">Level 1</a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" data-target=".items">Items</a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url('checksheet') ?>" class="nav-link go-checksheet"
                onclick="window.location=this.href; return true;">Go to checksheet</a>
        </li>
    </ul>
</div>

<div class="row g-3 w-100">
    <!-- level 0 dashboard -->
    <div class="level0 col-12 col-lg-12">
        <div class="card p-3">
            <h4 class="mb-5">Buildings with Findings</h4>
            <div class="row g-3 d-flex justify-content-center align-items-center" id="buildings-container">

                <!-- BUILDING CARDS WILL BE RENDERED ON THIS SECTION -->

            </div>
        </div>
    </div>

    <div class="col-12 col-lg-12 items">
        <div class="card p-3">
            <h4 class="mb-3 itm">Items</h4>
            <div class="input-group mb-3 w-25 d-flex">
                <span class="input-group-text" id="basic-addon1"><i class='bx bx-search'></i> </span>
                <input type="search" class="form-control w-25" id="searchInput" placeholder="Search item..."
                    aria-label="Search item..." aria-describedby="basic-addon1">
                </div>
            <div class="row gap-3 d-flex justify-content-center align-items-center" id="itemsContainer"></div>
        </div>
    </div>

    <!-- Map Column -->
    <div class="col-12 col-lg-12 level1 hidden">
        <div class="card p-3">
            <h4 class="mb-3">Site Map</h4>
            <div class="map-container">
                <img id="siteMap" src="<?= base_url('images/site_map.png') ?>" alt="Site Map">

                <?php if (isset($areas) && is_array($areas)): ?>
                    <?php foreach ($areas as $area): ?>
                        <?php
                        $left_percent = ($area['x_coords'] / $original_image_width) * 84;
                        $top_percent = ($area['y_coords'] / $original_image_height) * 100;
                        ?>
                        <div class="marker <?= strtolower($area['status']) ?? 'null' ?>"
                            style="left: <?= $left_percent ?>%; top: <?= $top_percent ?>%;"
                            data-area-id="<?= htmlspecialchars($area['area_id']) ?>">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($user_role === 'ADMINISTRATOR'): ?>
                <div class="coords mt-3">
                    X: <span id="x">0</span> | Y: <span id="y">0</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Column -->
    <!-- <div class="col-12 col-lg-3">
        <div class="d-flex flex-column gap-3">
            <div class="card text-center p-3 border-primary border-top">
                <h5>Total Areas</h5>
                <p class="fs-3 fw-bold total-areas">0</p>
            </div>
            <div class="card text-center p-3 border-success border-top">
                <h5>Areas with NG</h5>
                <p class="fs-3 fw-bold areas-with-ng">0</p>
            </div>
            <div class="card text-center p-3 border-info border-top">
                <h5>Pending Actions</h5>
                <p class="fs-3 fw-bold pending-actions">0</p>
            </div>
            <div class="card text-center p-3 border-danger border-top">
                <h5>Inspections Today</h5>
                <p class="fs-3 fw-bold inspections-today">0</p>
            </div>
        </div>
    </div> -->
</div>

<div class="modal fade" id="ngItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Item Details: <span id="modalItemName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table id="itemDetailsTable" class="table table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>Building</th>
                            <th>Area</th>
                            <th>Item</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // SCRIPT FOR MAP COORDINATES (ADMIN ONLY)
    <?php if ($user_role === 'ADMINISTRATOR'): ?>
            (function () {
                const siteMap = document.getElementById('siteMap');
                if (!siteMap) return;

                const xOut = document.getElementById('x');
                const yOut = document.getElementById('y');

                siteMap.addEventListener('mousemove', function (event) {
                    const rect = siteMap.getBoundingClientRect();
                    const relX = event.clientX - rect.left;
                    const relY = event.clientY - rect.top;
                    const naturalWidth = siteMap.naturalWidth;
                    const naturalHeight = siteMap.naturalHeight;
                    const displayWidth = rect.width;
                    const displayHeight = rect.height;
                    const scaledX = (relX / displayWidth) * naturalWidth;
                    const scaledY = (relY / displayHeight) * naturalHeight;
                    xOut.textContent = Math.round(scaledX);
                    yOut.textContent = Math.round(scaledY);
                });

                siteMap.addEventListener('click', function (event) {
                    const rect = siteMap.getBoundingClientRect();
                    const relX = event.clientX - rect.left;
                    const relY = event.clientY - rect.top;
                    const naturalWidth = siteMap.naturalWidth;
                    const naturalHeight = siteMap.naturalHeight;
                    const displayWidth = rect.width;
                    const displayHeight = rect.height;
                    const scaledX = (relX / displayWidth) * naturalWidth;
                    const scaledY = (relY / displayHeight) * naturalHeight;
                    // Using a simple alert for demonstration
                    alert(`Clicked at X: ${Math.round(scaledX)}, Y: ${Math.round(scaledY)}`);
                });
            })();
    <?php endif; ?>

    $(document).ready(function () {
        // get area count
        $.ajax({
            url: '<?= base_url('dashboard/get_area_count') ?>',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                $('.total-areas').text(response.count);
            },
            error: function (xhr, status, error) {
                $('.total-areas').text('0');
            }
        });

        // get areas with ng status
        $.ajax({
            url: '<?= base_url('dashboard/get_area_ng') ?>',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                $('.areas-with-ng').text(response.count);
            },
            error: function (xhr, status, error) {
                $('.areas-with-ng').text('0');
            }
        });

        // get pending actions
        $.ajax({
            url: '<?= base_url('dashboard/get_pending_actions') ?>',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                $('.pending-actions').text(response.count);
            },
            error: function (xhr, status, error) {
                $('.pending-actions').text('0');
            }
        });

        // get for inspections
        $.ajax({
            url: '<?= base_url('dashboard/get_inspections') ?>',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                $('.inspections-today').text(response.count);
            },
            error: function (xhr, status, error) {
                $('.inspections-today').text('0');
            }
        });

        // Use a delegated event listener for efficiency
        $(document).on('mouseenter', '.marker', function () {
            const marker = $(this);
            const areaId = marker.data('area-id');

            // Check if data is already loaded to avoid multiple requests
            if (marker.data('tooltip-loaded')) {
                return;
            }

            // Make an AJAX call to the new endpoint
            $.ajax({
                url: `<?= base_url('dashboard/area-details/') ?>${areaId}`,
                type: 'GET',
                dataType: 'json',
                // Inside your success function
                success: function (response) {
                    if (response) {
                        // Create the tooltip content dynamically
                        const tooltipContent = `
                            <p><strong>Area:</strong> ${response.area_name}</p>
                            <p><strong>Findings:</strong> ${response.findings_names}</p>
                            <p><strong>Findings Image:</strong> <img src="<?= base_url('uploads/findings/') ?>${response.finding_image}" alt="Finding Image"></p>
                        `; // Removed max-width: 100px from here

                        // Initialize the tooltip with the dynamic content
                        marker.attr('title', ''); // Clear existing title
                        marker.tooltip({
                            html: true,
                            title: tooltipContent,
                            placement: 'top'
                        }).tooltip('show');

                        // Set a flag to prevent re-fetching
                        marker.data('tooltip-loaded', true);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching area details:", error);
                }
            });
        });

        // Handle mouseleave to hide the tooltip
        $(document).on('mouseleave', '.marker', function () {
            $(this).tooltip('hide');
        });

        // tabs functionality
        const tabs = $('.nav-link');
        const contentSections = $('.level0, .level1, .items');

        tabs.on('click', function (e) {
            e.preventDefault();

            // get the target
            const target = $(this).data('target');

            tabs.removeClass('active');
            $(this).addClass('active');

            contentSections.addClass('hidden');

            $(target).removeClass('hidden');
        });

        // get all buildings
        function loadBuildings() {
            $.ajax({
                url: '<?= base_url('dashboard/get_all_buildings') ?>',
                type: 'GET',
                dataType: 'json',
                success: function (buildings) {
                    const container = $('#buildings-container');
                    container.empty();

                    if (buildings.length > 0) {
                        buildings.forEach(building => {
                            const btnClass = building.ng_count > 0 ? 'btn-danger' : 'btn-success';

                            const buildingHtml = `
                                <div class="col-5 col-lg-4">
                                    <div class="box-content">
                                        <div class="bldng-name">
                                            <h5 class="building_name text-center">${building.building_name}</h5>
                                        </div>
                                        <a href="#" class="p-4 btn w-100 d-flex justify-content-center align-items-center go-to-level1 ${btnClass}">
                                            <h2 class="ngAndOkCount text-white">${building.ng_count}</h2>
                                        </a>
                                    </div>
                                </div>
                            `;
                            container.append(buildingHtml);
                        });
                    } else {
                        container.html('<p>No buildings found.</p>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching buildings:", error);
                    $('#buildings-container').html('<p>Failed to load building data.</p>');
                }
            });
        }

        loadBuildings();

        // trigger tab changed when building box clicked
        $(document).on('click', '.go-to-level1', function (e) {
            e.preventDefault();

            $('.nav-link[data-target=".level1"]').trigger('click');
        });

        let searchTimeout;

        $('#searchInput').on('keyup', function () {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();

            searchTimeout = setTimeout(function () {
                loadItems(searchTerm);
            }, 100);
        });

        loadItems('');

        /* GET ALL ITEMS WITH NG STATUS */
        function loadItems(searchTerm = '') {
            $.ajax({
                url: '<?= base_url('dashboard/get_all_items') ?>',
                type: 'GET',
                data: { search: searchTerm },
                dataType: 'json',
                success: function (items) {
                    const container = $('#itemsContainer');
                    container.empty();

                    if (items.length > 0) {
                        items.forEach(item => {
                            const [ngCountStr] = item.ng_ratio.split('/');
                            const ngCount = parseInt(ngCountStr, 10);
                            const btnClass = ngCount >= 1 ? 'btn-danger' : 'btn-success';

                            const itemHtml = `
                                <div class="col-3" style="height: 100px;">
                                    <button type="button"
                                        id="ngItem"
                                        class="btn ${btnClass} p-3 w-100 shadow open-item-modal"
                                        style="width: 50px; height: 100px;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#ngItemModal"
                                        data-item="${item.item_name}">
                                            ${item.item_name}<br>${item.ng_ratio}
                                    </button>
                                </div>
                            `;
                            container.append(itemHtml);
                        });
                    } else {
                        container.html('<p>No items found.</p>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching items:", error);
                    $('#itemsContainer').html('<p>Failed to load items data.</p>');
                }
            });
        }

        loadItems();

        /* ITEMS DASHBOARD */
        let itemDetailsTable;
    
        $(document).on('click', '.open-item-modal', function(){
            const itemName = $(this).data('item');
            $('#modalItemName').text(itemName);
    
            if($.fn.DataTable.isDataTable('#itemDetailsTable')){
                $('#itemDetailsTable').DataTable().destroy();
            }
    
            itemDetailsTable = $('#itemDetailsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '<?= base_url('dashboard/get-items-details') ?>',
                    type: 'POST',
                    data: function(d){
                        d.item_name = itemName;
                    }
                },
                columns: [
                    { data: 'building_name' },
                    { data: 'area_name' },
                    { data: 'item_name' },
                    {
                        data: 'status',
                        render: function(data, type, row){
                            if(data === 'NG'){
                                return `<span class="badge bg-danger">${data}</span>`;
                            }else{
                                return `<span class="badge bg-success">${data}</span>`;
                            }
                        }
                    },
                ]
            });
        });

    });

</script>
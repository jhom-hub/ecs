<?php
$original_image_width = 1200;
$original_image_height = 800;
$user_role = session()->get('role') ?? '';
?>

<style>
    /* Base styles */
    body {
        background: #f4f4f4;
    }

    /* Map container styles */
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

    /* Marker styles */
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

    /* Animated marker effects */
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

    /* Coordinates display */
    .coords {
        margin-top: 15px;
        padding: 10px;
        background: #fff;
        border: 1px solid #ccc;
        display: inline-block;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Findings table styles */
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

    /* Tooltip styles */
    .tooltip-inner {
        max-width: 250px !important;
        text-align: left;
        padding: 10px;
    }

    .tooltip-inner img {
        max-width: 100% !important;
        height: auto;
        display: block;
        margin-top: 5px;
        border-radius: 4px;
    }

    /* Utility classes */
    .hidden {
        display: none;
    }

    /* Building name styles */
    .bldng-name {
        margin-bottom: 20px !important;
    }

    .building_name {
        margin-bottom: -17px !important;
    }
</style>

<!-- Navigation Tabs -->
<div class="tabs mb-1">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a href="#" class="nav-link active lvl0" aria-current="page" data-target=".level0">Level 0</a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" data-target=".level1">Level 1</a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" data-target=".items">Items</a>
        </li>
    </ul>
</div>

<!-- Main Content Container -->
<div class="row g-3 w-100">
    <!-- Level 0: Buildings Dashboard -->
    <div class="level0 col-12 col-lg-12">
        <div class="card p-3">
            <h4 class="mb-5">Buildings with Findings</h4>
            <div class="row g-1 d-flex justify-content-center align-items-center" id="buildings-container">
                <!-- Building cards will be dynamically rendered here -->
            </div>
        </div>
    </div>

    <!-- Items Dashboard -->
    <div class="col-12 col-lg-12 items hidden">
        <div class="card p-3">
            <h4 class="mb-3 itm">Items</h4>

            <!-- Search and Filter Controls -->
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div class="searching input-group w-25">
                    <span class="input-group-text" id="basic-addon1">
                        <i class='bx bx-search'></i>
                    </span>
                    <input type="search" class="form-control w-25" id="searchInput" placeholder="Search item..."
                        aria-label="Search item" aria-describedby="basic-addon1">
                </div>

                <div class="filtering input-group w-25">
                    <input type="date" name="start_date" id="startDate" class="form-control bg-light"
                        placeholder="Start date">
                    <input type="date" name="end_date" id="endDate" class="form-control bg-light"
                        placeholder="End date">
                    <button type="button" class="btn btn-secondary">
                        <i class='bx bx-filter'></i>
                    </button>
                </div>
            </div>

            <!-- Items Container -->
            <div class="row g-1 d-flex justify-content-start align-items-center" id="itemsContainer">
                <!-- Items will be dynamically rendered here -->
            </div>
        </div>
    </div>

    <!-- Level 1: Site Map -->
    <div class="col-12 col-lg-12 level1 hidden">
        <div class="card p-3">
            <h4 class="mb-3">Site Map</h4>
            <div class="map-container">
                <div id="mapWrapper">
                    <img id="siteMap" src="<?= base_url('images/site_map.png') ?>" alt="Site Map">

                    <!-- Dynamic Area Markers -->
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
            </div>

            <!-- Coordinate Display (Admin Only) -->
            <?php if ($user_role === 'ADMINISTRATOR'): ?>
                <div class="coords mt-3">
                    X: <span id="x">0</span> | Y: <span id="y">0</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chart Dashboard (Currently Hidden) -->
    <div class="dashboardGraphs col-12 col-lg-12">
        <div class="card p-3">
            <div id="chart"></div>
        </div>
    </div>
</div>

<!-- Item Details Modal -->
<div class="modal fade" id="ngItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Item Details: <span id="modalItemName"></span>
                </h5>
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
    // Initialize map coordinates functionality for administrators
    <?php if ($user_role === 'ADMINISTRATOR'): ?>
            (function initializeMapCoordinates() {
                const siteMap = document.getElementById('siteMap');
                if (!siteMap) return;

                const xOut = document.getElementById('x');
                const yOut = document.getElementById('y');

                function updateCoordinates(event) {
                    const rect = siteMap.getBoundingClientRect();
                    const relX = event.clientX - rect.left;
                    const relY = event.clientY - rect.top;
                    const naturalWidth = siteMap.naturalWidth;
                    const naturalHeight = siteMap.naturalHeight;
                    const displayWidth = rect.width;
                    const displayHeight = rect.height;
                    const scaledX = (relX / displayWidth) * naturalWidth;
                    const scaledY = (relY / displayHeight) * naturalHeight;

                    return {
                        x: Math.round(scaledX),
                        y: Math.round(scaledY)
                    };
                }

                siteMap.addEventListener('mousemove', function (event) {
                    const coords = updateCoordinates(event);
                    xOut.textContent = coords.x;
                    yOut.textContent = coords.y;
                });

                siteMap.addEventListener('click', function (event) {
                    const coords = updateCoordinates(event);
                    alert(`Clicked at X: ${coords.x}, Y: ${coords.y}`);
                });
            })();
    <?php endif; ?>

    $(document).ready(function () {
        // Initialize dashboard statistics
        function initializeDashboardStats() {
            const statEndpoints = [
                { url: 'dashboard/get_area_count', target: '.total-areas' },
                { url: 'dashboard/get_area_ng', target: '.areas-with-ng' },
                { url: 'dashboard/get_pending_actions', target: '.pending-actions' },
                { url: 'dashboard/get_inspections', target: '.inspections-today' }
            ];

            statEndpoints.forEach(endpoint => {
                $.ajax({
                    url: `<?= base_url('${endpoint.url}') ?>`,
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        $(endpoint.target).text(response.count);
                    },
                    error: function () {
                        $(endpoint.target).text('0');
                    }
                });
            });
        }

        // Handle marker tooltip interactions
        function initializeMarkerTooltips() {
            // Prevent carousel control clicks from bubbling
            $(document).on('click', '.carousel-control-prev, .carousel-control-next', function (e) {
                e.stopPropagation();
            });

            // Handle marker clicks
            $(document).on('click', '.marker', function (e) {
                e.stopPropagation();

                const marker = $(this);
                const areaId = marker.data('area-id');

                // Clear existing tooltips
                $('.marker').tooltip('dispose');
                $('.marker').data('tooltip-loaded', false);

                // Fetch area details
                $.ajax({
                    url: `<?= base_url('dashboard/area-details/') ?>${areaId}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response) {
                            const tooltipContent = buildTooltipContent(response);
                            showMarkerTooltip(marker, tooltipContent);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Error fetching area details:", error);
                    }
                });
            });

            // Hide tooltips when clicking elsewhere
            $(document).on('click', function () {
                $('.marker').tooltip('dispose');
                $('.marker').data('tooltip-loaded', false);
            });
        }

        function buildTooltipContent(response) {
            const imageFilenames = response.finding_images ? response.finding_images.split(',') : [];
            let carouselInnerHtml = '';
            let carouselIndicatorsHtml = '';

            if (imageFilenames.length > 0) {
                imageFilenames.forEach((filename, index) => {
                    const isActive = index === 0 ? 'active' : '';
                    carouselInnerHtml += `
                        <div class="carousel-item ${isActive}">
                            <a href="<?= base_url('checksheet'); ?>?id=${response.checksheet_id}">
                                <img src="<?= base_url('uploads/findings/') ?>${filename}" 
                                    class="d-block w-100" 
                                    alt="Finding Image" 
                                    style="height: 250px !important;"
                                >
                            </a>
                        </div>
                    `;
                    carouselIndicatorsHtml += `
                        <button type="button" 
                                data-bs-target="#findingsCarousel" 
                                data-bs-slide-to="${index}" 
                                class="${isActive}" 
                                aria-current="${isActive}" 
                                aria-label="Slide ${index + 1}">
                        </button>
                    `;
                });
            } else {
                carouselInnerHtml = '<p>No images found.</p>';
            }

            return `
                <p><strong>Area:</strong> ${response.area_name}</p>
                <p><strong>Item:</strong> ${response.item_names}</p>
                <p><strong>Findings:</strong> ${response.findings_names}</p>
                <div id="findingsCarousel" class="carousel slide" data-bs-ride="carousel">
                    ${imageFilenames.length > 1 ? `
                        <div class="carousel-indicators">
                            ${carouselIndicatorsHtml}
                        </div>
                    ` : ''}
                    <div class="carousel-inner">
                        ${carouselInnerHtml}
                    </div>
                    ${imageFilenames.length > 1 ? `
                        <button class="carousel-control-prev" type="button" data-bs-target="#findingsCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#findingsCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    ` : ''}
                </div>
            `;
        }

        function showMarkerTooltip(marker, content) {
            marker.tooltip({
                html: true,
                title: content,
                placement: 'top',
                trigger: 'manual',
                sanitize: false,
                delay: { "show": 0, "hide": 0 }
            }).tooltip('show');

            marker.data('tooltip-loaded', true);
        }

        // Initialize tab navigation
        function initializeTabNavigation() {
            const tabs = $('.nav-link');
            const contentSections = $('.level0, .level1, .items');

            tabs.on('click', function (e) {
                e.preventDefault();
                const target = $(this).data('target');

                tabs.removeClass('active');
                $(this).addClass('active');
                contentSections.addClass('hidden');
                $(target).removeClass('hidden');

                // 👇 Reset Panzoom if going back to Level 0
                if (target === '.level0' && window.siteMapPanzoom) {
                    if (typeof window.siteMapPanzoom.reset === "function") {
                        window.siteMapPanzoom.reset();
                    } else if (typeof window.siteMapPanzoom.resetTransform === "function") {
                        window.siteMapPanzoom.resetTransform();
                    }
                }
            });
        }


        // Load and display buildings
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
                            const buildingHtml = createBuildingCard(building);
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

        function createBuildingCard(building) {
            const btnClass = building.ng_count > 0 ? 'btn-danger' : 'btn-success';
            return `
                <div class="col-5 col-lg-4 mb-3">
                    <div class="box-content">
                        <div class="bldng-name">
                            <h5 class="building_name text-center">${building.building_name}</h5>
                        </div>
                        <a href="#"
                           class="p-4 btn w-100 d-flex justify-content-center align-items-center go-to-level1 ${btnClass}"
                           data-x="${building.x_coords}"
                           data-y="${building.y_coords}">
                            <h2 class="ngAndOkCount text-white">${building.ng_count}</h2>
                        </a>
                    </div>
                </div>
            `;
        }

        // Initialize map panning and zooming
        function initializeMapInteraction() {
            const elem = document.getElementById("mapWrapper");
            window.siteMapPanzoom = Panzoom(elem, {
                maxScale: 5,
                minScale: 1,
                contain: "outside"
            });

            elem.parentElement.addEventListener("wheel", window.siteMapPanzoom.zoomWithWheel);
        }

        // Handle building click to zoom to location
        function initializeBuildingZoom() {
            $(document).on('click', '.go-to-level1', function (e) {
                e.preventDefault();

                const x = parseFloat($(this).data('x'));
                const y = parseFloat($(this).data('y'));

                $('.nav-link[data-target=".level1"]').trigger('click');

                if (!window.siteMapPanzoom) {
                    console.warn("Panzoom not initialized");
                    return;
                }
                // window.siteMapPanzoom.reset();
                zoomToCoordinates(x, y);
            });
        }


        function zoomToCoordinates(x, y) {
            const zoomScale = 2.5;
            const img = document.getElementById("siteMap");
            const rect = img.getBoundingClientRect();
            const originalWidth = 1200;
            const originalHeight = 800;

            const clientX = rect.left + (x / originalWidth) * rect.width;
            const clientY = rect.top + (y / originalHeight) * rect.height;

            console.log("Zooming to:", clientX, clientY);
            window.siteMapPanzoom.zoomToPoint(zoomScale, { clientX, clientY });
        }

        // Initialize item search and filtering
        function initializeItemSearch() {
            let searchTimeout;

            $('#searchInput').on('keyup', function () {
                clearTimeout(searchTimeout);
                const searchTerm = $(this).val();

                searchTimeout = setTimeout(() => {
                    loadItems(searchTerm);
                }, 100);
            });

            $(document).on('click', '.filtering button', function () {
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();

                if (startDate && endDate) {
                    loadItems('', startDate, endDate);
                } else {
                    loadItems();
                }
            });
        }

        // Load and display items
        function loadItems(searchTerm = '', startDate = '', endDate = '') {
            $.ajax({
                url: '<?= base_url('dashboard/get_all_items') ?>',
                type: 'GET',
                data: {
                    search: searchTerm,
                    start_date: startDate,
                    end_date: endDate
                },
                dataType: 'json',
                success: function (items) {
                    const container = $('#itemsContainer');
                    container.empty();

                    if (items.length > 0) {
                        items.forEach(item => {
                            const itemHtml = createItemCard(item);
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

        function createItemCard(item) {
            const [ngCountStr] = item.ng_ratio.split('/');
            const ngCount = parseInt(ngCountStr, 10);
            const btnClass = ngCount >= 1 ? 'btn-danger' : 'btn-success';

            return `
                <div class="col-3" style="height: 100px;">
                    <button type="button"
                            id="ngItem"
                            class="btn ${btnClass} p-3 w-100 shadow open-item-modal"
                            style="width: 50px; height: 100px; font-size: 14pt;"
                            data-bs-toggle="modal"
                            data-bs-target="#ngItemModal"
                            data-item="${item.item_name}">
                        ${item.item_name}<br>${item.ng_ratio}
                    </button>
                </div>
            `;
        }

        // Initialize item details modal
        function initializeItemModal() {
            let itemDetailsTable;

            $(document).on('click', '.open-item-modal', function () {
                const itemName = $(this).data('item');
                $('#modalItemName').text(itemName);

                if ($.fn.DataTable.isDataTable('#itemDetailsTable')) {
                    $('#itemDetailsTable').DataTable().destroy();
                }

                itemDetailsTable = $('#itemDetailsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '<?= base_url('dashboard/get-items-details') ?>',
                        type: 'POST',
                        data: function (d) {
                            d.item_name = itemName;
                        }
                    },
                    columns: [
                        { data: 'building_name' },
                        { data: 'area_name' },
                        { data: 'item_name' },
                        {
                            data: 'status',
                            render: function (data, type, row) {
                                const badgeClass = data === 'NG' ? 'bg-danger' : 'bg-success';
                                return `<span class="badge ${badgeClass}">${data}</span>`;
                            }
                        }
                    ]
                });
            });
        }

        // Initialize date picker
        function initializeDatePicker() {
            flatpickr("#startDate, #endDate", {
                enableTime: false,
                dateFormat: 'Y-m-d',
            });
        }

        // Initialize all components
        initializeDashboardStats();
        initializeMarkerTooltips();
        initializeTabNavigation();
        initializeMapInteraction();
        initializeBuildingZoom();
        initializeItemSearch();
        initializeItemModal();
        initializeDatePicker();

        // Load initial data
        loadBuildings();
        loadItems();
    });
</script>
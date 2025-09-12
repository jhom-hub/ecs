<?php
// Default values for the map image dimensions
$original_image_width = 1200;
$original_image_height = 800;
// Safely get the user role from the session
$user_role = session()->get('role') ?? 'USER';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Note: Assuming Bootstrap, jQuery, and other libraries are loaded in your main layout -->
    <style>
        /* CSS Variables for easier theme management */
        :root {
            --primary-color: #007bff;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --text-color: #212529;
            --border-radius: 8px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: #f4f7f9;
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
        }

        /* Card and UI element styling */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: all 0.3s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .nav-tabs .nav-link {
            color: var(--text-color);
            border-bottom: 3px solid transparent;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-color: var(--primary-color);
            background-color: transparent;
        }
        
        /* Map container styles */
        .map-container {
            position: relative;
            width: 100%;
            overflow: hidden; /* Important for Panzoom */
            border-radius: var(--border-radius);
            background-color: var(--light-gray);
        }

        #mapWrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 600px; /* Pre-define height to prevent layout shift */
            position: relative; /* Crucial for correct marker positioning */
        }
        
        /* Map Loader */
        .map-loader {
            text-align: center;
        }

        .map-loader .spinner {
            border: 4px solid rgba(0,0,0,0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: var(--primary-color);
            margin: 0 auto 10px;
            animation: spin 1s ease infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .map-container img {
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: var(--border-radius);
            cursor: grab;
        }

        .map-container img:active {
            cursor: grabbing;
        }

        /* Marker styles with sonar animation */
        .marker {
            position: absolute;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            cursor: pointer;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.6);
            transition: transform 0.2s ease;
        }
/* 
        .marker:hover {
            transform: translate(-50%, -50%) scale(1.2);
        } */

        .marker.ng { background-color: var(--danger-color); }
        .marker.ok { background-color: var(--success-color); }
        .marker.pending { background-color: #ffc107; }

        .marker.ng::before,
        .marker.ng::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 100%;
            height: 100%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            background-color: var(--danger-color);
            z-index: -1;
            animation: sonar 1.5s ease-out infinite;
        }

        .marker.ng::after {
            animation-delay: 0.5s;
        }

        @keyframes sonar {
            0% { transform: translate(-50%, -50%) scale(1); opacity: 0.6; }
            100% { transform: translate(-50%, -50%) scale(4); opacity: 0; }
        }

        /* Tooltip styles */
        .tooltip-inner {
            max-width: 300px !important;
            text-align: left;
            padding: 12px;
            background-color: var(--dark-gray);
        }
        .bs-tooltip-top .tooltip-arrow::before {
             border-top-color: var(--dark-gray);
        }

        /* Utility classes */
        .hidden { display: none; }
    </style>
</head>
<body>

<!-- Navigation Tabs -->
<div class="tabs mb-3">
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
    </ul>
</div>

<!-- Main Content Container -->
<div class="row g-4 w-100">
    <!-- Level 0: Buildings Dashboard -->
    <div class="level0 col-12">
        <div class="card p-3">
            <h4 class="mb-4">Buildings with Findings</h4>
            <div class="row g-3 justify-content-center" id="buildings-container">
                <!-- Building cards will be dynamically rendered here -->
            </div>
        </div>
    </div>

    <!-- Items Dashboard -->
    <div class="items col-12 hidden">
        <div class="card p-3">
            <h4 class="mb-3">Items Dashboard</h4>
            <!-- Search and Filter Controls -->
            <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                 <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text"><i class='bx bx-search'></i></span>
                    <input type="search" class="form-control" id="searchInput" placeholder="Search item...">
                </div>
                <div class="input-group" style="max-width: 400px;">
                    <input type="date" name="start_date" id="startDate" class="form-control bg-light">
                    <input type="date" name="end_date" id="endDate" class="form-control bg-light">
                    <button type="button" class="btn btn-secondary" id="filterBtn"><i class='bx bx-filter'></i> Filter</button>
                </div>
            </div>
            <!-- Items Container -->
            <div class="row g-2 justify-content-start" id="itemsContainer">
                <!-- Items will be dynamically rendered here -->
            </div>
        </div>
    </div>

    <!-- Level 1: Site Map -->
    <div class="level1 col-12 hidden">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Site Map</h4>
                 <?php if ($user_role === 'ADMINISTRATOR'): ?>
                <div class="coords p-2 bg-light border rounded">
                    X: <span id="x">0</span> | Y: <span id="y">0</span>
                </div>
                <?php endif; ?>
            </div>
            <div class="map-container">
                <div id="mapWrapper">
                    <div class="map-loader">
                        <div class="spinner"></div>
                        <p>Loading Map...</p>
                    </div>
                    <img id="siteMap" src="<?= base_url('images/site_map.png') ?>" alt="Site Map" style="visibility:hidden;">

                    <!-- Dynamic Area Markers -->
                    <?php if (isset($areas) && is_array($areas)): ?>
                        <?php foreach ($areas as $area): ?>
                            <?php
                            $left_percent = ($area['x_coords'] / $original_image_width) * 100;
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
        </div>
    </div>
</div>

<!-- Item Details Modal -->
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
$(document).ready(function () {
    /**
     * Main application object to encapsulate dashboard logic.
     * This improves structure and avoids polluting the global namespace.
     */
    const DashboardApp = {
        // Configuration and constants
        config: {
            baseUrl: "<?= base_url() ?>",
            api: {
                buildings: 'dashboard/get_all_buildings',
                items: 'dashboard/get_all_items',
                itemDetails: 'dashboard/get-items-details',
                areaDetails: 'dashboard/area-details/'
            },
            map: {
                originalWidth: <?= $original_image_width ?>,
                originalHeight: <?= $original_image_height ?>,
                zoomScale: 2.5
            }
        },

        // Application state
        state: {
            siteMapPanzoom: null,
            itemDetailsTable: null,
            searchTimeout: null
        },

        // Initializes the entire application
        init: function() {
            this.initTabs();
            this.initMap(); // This now handles the image loading check
            this.initItemSearchAndFilter();
            this.initItemModal();
            this.loadBuildings();
            this.loadItems();
        },

        // Sets up tab navigation
        initTabs: function() {
            $('.nav-link').on('click', function(e) {
                e.preventDefault();
                const target = $(this).data('target');
                $('.nav-link').removeClass('active');
                $(this).addClass('active');
                $('.level0, .level1, .items').addClass('hidden');
                $(target).removeClass('hidden');

                // Reset map zoom when returning to Level 0
                if ($(this).data('target') === '.level0' && DashboardApp.state.siteMapPanzoom) {
                    DashboardApp.state.siteMapPanzoom.reset();
                }
            });
        },

        /**
         * Initializes the map, ensuring the image is fully loaded before
         * attaching Panzoom and other dependent functionalities.
         */
        initMap: function() {
            const siteMapImage = document.getElementById('siteMap');
            const mapLoader = document.querySelector('.map-loader');

            if (!siteMapImage) return;

            const onImageLoad = () => {
                console.log("Site map image loaded. Initializing Panzoom.");
                mapLoader.style.display = 'none';
                siteMapImage.style.visibility = 'visible';

                const elem = document.getElementById("mapWrapper");
                this.state.siteMapPanzoom = Panzoom(elem, {
                    maxScale: 5,
                    minScale: 1,
                    contain: "outside",
                    cursor: "grab"
                });
                elem.parentElement.addEventListener("wheel", this.state.siteMapPanzoom.zoomWithWheel);

                // These functions depend on the map and Panzoom being ready
                this.initBuildingZoom();
                this.initMarkerTooltips();
                <?php if ($user_role === 'ADMINISTRATOR'): ?>
                this.initAdminCoords();
                <?php endif; ?>
            };

            // Check if the image is already loaded (from cache)
            if (siteMapImage.complete) {
                onImageLoad();
            } else {
                siteMapImage.addEventListener('load', onImageLoad);
                siteMapImage.addEventListener('error', () => {
                    mapLoader.innerHTML = '<p class="text-danger">Failed to load map image.</p>';
                });
            }
        },

        // Sets up coordinate display for administrators
        initAdminCoords: function() {
            const siteMap = document.getElementById('siteMap');
            const xOut = document.getElementById('x');
            const yOut = document.getElementById('y');

            const updateCoordinates = (event) => {
                const rect = siteMap.getBoundingClientRect();
                const scale = this.state.siteMapPanzoom.getScale();
                const pan = this.state.siteMapPanzoom.getPan();

                // Calculate the real mouse position on the original image
                const realX = (event.clientX - rect.left - pan.x) / scale;
                const realY = (event.clientY - rect.top - pan.y) / scale;

                const originalX = (realX / siteMap.clientWidth) * this.config.map.originalWidth;
                const originalY = (realY / siteMap.clientHeight) * this.config.map.originalHeight;

                xOut.textContent = Math.round(originalX);
                yOut.textContent = Math.round(originalY);
            };
            
            siteMap.addEventListener('mousemove', updateCoordinates);
        },

        // Loads and renders building data
        loadBuildings: function() {
            $.getJSON(`${this.config.baseUrl}${this.config.api.buildings}`)
                .done(buildings => {
                    const container = $('#buildings-container');
                    container.empty();
                    if (buildings.length) {
                        buildings.forEach(b => container.append(this.createBuildingCard(b)));
                    } else {
                        container.html('<p class="text-muted">No buildings found.</p>');
                    }
                })
                .fail(() => {
                    $('#buildings-container').html('<p class="text-danger">Failed to load building data.</p>');
                });
        },

        // Creates HTML for a single building card
        createBuildingCard: function(building) {
            const btnClass = building.ng_count > 0 ? 'btn-danger' : 'btn-success';
            return `
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card text-center">
                         <div class="card-header">${building.building_name}</div>
                         <div class="card-body">
                            <a href="#" class="btn ${btnClass} w-100 go-to-level1"
                               data-x="${building.x_coords}" data-y="${building.y_coords}">
                                <h2 class="display-4 text-white mb-0">${building.ng_count}</h2>
                                <small class="text-white-50">Findings</small>
                            </a>
                        </div>
                    </div>
                </div>`;
        },

        // Handles clicking a building to navigate and zoom
        initBuildingZoom: function() {
            $(document).on('click', '.go-to-level1', function(e) {
                e.preventDefault();
                const x = parseFloat($(this).data('x'));
                const y = parseFloat($(this).data('y'));

                $('.nav-link[data-target=".level1"]').trigger('click');
                
                // **THE FIX:** Use requestAnimationFrame to ensure the browser has
                // rendered the map tab *before* we try to calculate its dimensions for zooming.
                // This prevents a race condition where calculations were based on a hidden element.
                requestAnimationFrame(() => {
                    DashboardApp.zoomToCoordinates(x, y);
                });
            });
        },

        // Main zoom logic
        zoomToCoordinates: function(x, y) {
            if (!this.state.siteMapPanzoom) {
                console.warn("Panzoom not initialized, cannot zoom.");
                return;
            }
            const panzoom = this.state.siteMapPanzoom;
            const img = document.getElementById("siteMap");
            const wrapper = document.getElementById("mapWrapper");
            const container = wrapper.parentElement;

            // 1. Calculate the coordinates of the target point relative to the image's top-left corner
            const pointXOnImage = (x / this.config.map.originalWidth) * img.clientWidth;
            const pointYOnImage = (y / this.config.map.originalHeight) * img.clientHeight;

            // 2. Calculate the offset of the image within its wrapper (due to flexbox centering)
            const offsetXInWrapper = (wrapper.clientWidth - img.clientWidth) / 2;
            const offsetYInWrapper = (wrapper.clientHeight - img.clientHeight) / 2;

            // 3. Determine the point's coordinates relative to the wrapper's top-left corner
            const pointXInWrapper = offsetXInWrapper + pointXOnImage;
            const pointYInWrapper = offsetYInWrapper + pointYOnImage;
            
            // 4. Define the target scale
            const targetScale = this.config.map.zoomScale;

            // 5. Calculate the pan values needed to move the target point to the center of the viewport.
            // The formula is: pan = (viewport_center) - (scaled_point_position)
            const panX = (container.clientWidth / 2) - (pointXInWrapper * targetScale);
            const panY = (container.clientHeight / 2) - (pointYInWrapper * targetScale);
            
            // 6. Apply the zoom and pan transformations with animation.
            panzoom.zoom(targetScale, { animate: true });
            panzoom.pan(panX, panY, { animate: true });
        },

        // Initializes tooltips on map markers
        initMarkerTooltips: function() {
            $(document).on('click', '.marker', function(e) {
                e.stopPropagation();
                const marker = $(this);
                const areaId = marker.data('area-id');
                
                // Hide other tooltips
                $('.marker').not(marker).tooltip('hide');

                // Fetch data if not already loaded
                if (!marker.data('bs.tooltip')) {
                     $.getJSON(`${DashboardApp.config.baseUrl}${DashboardApp.config.api.areaDetails}${areaId}`)
                        .done(response => {
                            if (response) {
                                const tooltipContent = DashboardApp.buildTooltipContent(response);
                                marker.tooltip({
                                    html: true,
                                    title: tooltipContent,
                                    placement: 'top',
                                    trigger: 'manual',
                                    sanitize: false
                                }).tooltip('show');
                            }
                        })
                        .fail(() => console.error("Error fetching area details for tooltip."));
                } else {
                    marker.tooltip('toggle');
                }
            });

            $(document).on('click', () => $('.marker').tooltip('hide'));
        },
        
        buildTooltipContent: function(response) {
            const images = response.finding_images ? response.finding_images.split(',') : [];
            let carouselHtml = '<p>No images found.</p>';
            if (images.length > 0) {
                 const carouselId = `carousel-${response.area_id}`;
                 const items = images.map((img, i) => `
                    <div class="carousel-item ${i === 0 ? 'active' : ''}">
                        <img src="${this.config.baseUrl}uploads/findings/${img}" class="d-block w-100" alt="Finding Image" style="height: 150px; object-fit: cover;">
                    </div>`).join('');

                carouselHtml = `
                    <div id="${carouselId}" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner rounded">${items}</div>
                        ${images.length > 1 ? `
                        <button class="carousel-control-prev" type="button" data-bs-target="#${carouselId}" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                        <button class="carousel-control-next" type="button" data-bs-target="#${carouselId}" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                        ` : ''}
                    </div>`;
            }

            return `
                <div class="p-1">
                    <p class="mb-1"><strong>Area:</strong> ${response.area_name}</p>
                    <p class="mb-1"><strong>Item:</strong> ${response.item_names}</p>
                    <p class="mb-2"><strong>Findings:</strong> ${response.findings_names}</p>
                    ${carouselHtml}
                </div>`;
        },
        
        // Loads item data with optional search and date filters
        loadItems: function(searchTerm = '', startDate = '', endDate = '') {
            $.getJSON(`${this.config.baseUrl}${this.config.api.items}`, { search: searchTerm, start_date: startDate, end_date: endDate })
                .done(items => {
                    const container = $('#itemsContainer');
                    container.empty();
                    if (items.length) {
                        items.forEach(item => container.append(this.createItemCard(item)));
                    } else {
                        container.html('<p class="text-muted">No items found.</p>');
                    }
                })
                .fail(() => $('#itemsContainer').html('<p class="text-danger">Failed to load items data.</p>'));
        },

        // Creates HTML for a single item card
        createItemCard: function(item) {
            const [ngCount] = item.ng_ratio.split('/').map(Number);
            const btnClass = ngCount > 0 ? 'btn-danger' : 'btn-success';
            return `
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <button type="button" class="btn ${btnClass} p-2 w-100 h-100 shadow-sm open-item-modal"
                            data-bs-toggle="modal" data-bs-target="#ngItemModal" data-item="${item.item_name}">
                        <strong>${item.item_name}</strong><br>${item.ng_ratio}
                    </button>
                </div>`;
        },

        // Sets up item search and date filtering
        initItemSearchAndFilter: function() {
            $('#searchInput').on('keyup', () => {
                clearTimeout(this.state.searchTimeout);
                this.state.searchTimeout = setTimeout(() => {
                    this.loadItems($('#searchInput').val(), $('#startDate').val(), $('#endDate').val());
                }, 300); // Debounce search input
            });
            $('#filterBtn, #startDate, #endDate').on('click change', () => {
                this.loadItems($('#searchInput').val(), $('#startDate').val(), $('#endDate').val());
            });
        },

        // Initializes the item details modal and its DataTable
        initItemModal: function() {
            $('#ngItemModal').on('show.bs.modal', (event) => {
                const button = $(event.relatedTarget);
                const itemName = button.data('item');
                $('#modalItemName').text(itemName);

                if ($.fn.DataTable.isDataTable('#itemDetailsTable')) {
                    $('#itemDetailsTable').DataTable().destroy();
                }

                this.state.itemDetailsTable = $('#itemDetailsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${this.config.baseUrl}${this.config.api.itemDetails}`,
                        type: 'POST',
                        data: { item_name: itemName }
                    },
                    columns: [
                        { data: 'building_name' },
                        { data: 'area_name' },
                        { data: 'item_name' },
                        {
                            data: 'status',
                            render: (data) => `<span class="badge ${data === 'NG' ? 'bg-danger' : 'bg-success'}">${data}</span>`
                        }
                    ]
                });
            });
        }
    };

    // Run the application
    DashboardApp.init();
});
</script>

</body>
</html>


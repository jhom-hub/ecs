<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>ECS | Environmental Check Sheet</title>
    <?= $this->include('layout/headerlinks') ?>
    <script>
        const baseUrl = "<?= base_url() ?>";
        const basePath = "<?= rtrim(parse_url(base_url(), PHP_URL_PATH), '/') ?>";
    </script>
</head>
<body>
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?= $this->include('layout/sidebar') ?>

        <div class="layout-page">
            <?= $this->include('layout/topbar') ?>

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y" id="content-area">
                    </div>
                <?= $this->include('layout/footer') ?>
            </div>
        </div>
    </div>
    <div class="layout-overlay layout-menu-toggle"></div>
</div>

    <?= $this->include('layout/footerlinks') ?>

    <script>
    $(document).ready(function() {
        function loadPage(page, title, pushState = true) {
            $.ajax({
                url: baseUrl + 'load-content',
                type: 'POST',
                data: { page: page },
                dataType: 'json',
                beforeSend: function() {
                    $('#content-area').html('<div class="text-center p-5">Loading...</div>');
                },
                success: function(response) {
                    if (response.success) {
                        loadNotifications(); // ✅ Refresh notifications on every page load
                        $('#content-area').html(response.content);
                        document.title = (title || response.title) + ' | ECS';

                        if (pushState) {
                            history.pushState({ page: page, title: response.title }, '', basePath + '/' + page);
                        }

                        // Update active menu
                        $('.menu-item').removeClass('active open');
                        const activeLink = $('.load-page[data-page="' + page + '"]');
                        activeLink.closest('.menu-item').addClass('active');

                        // If the item is in a submenu, open the parent menu
                        if (activeLink.closest('.menu-sub').length) {
                            activeLink.closest('.menu-item').parents('.menu-item').addClass('active open');
                        }
                    } else {
                        $('#content-area').html('<div class="alert alert-danger">' + response.error + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#content-area').html('<div class="alert alert-danger">Error: ' + error + '</div>');
                }
            });
        }

        // Handle menu click
        $(document).on('click', '.load-page', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            var title = $(this).data('title');
            loadPage(page, title);
        });

        // Handle browser back/forward
        window.onpopstate = function(event) {
            if (event.state && event.state.page) {
                loadPage(event.state.page, event.state.title, false);
            }
        };

        function getInitialPage() {
            // Get the full path from the URL
            const pathname = window.location.pathname;
            // Remove the base path to get the relative path
            const relativePath = pathname.startsWith(basePath) ? pathname.substring(basePath.length) : pathname;
            // Clean up slashes and get the first segment (the page name)
            let page = relativePath.replace(/^\/|\/$/g, '').split('/')[0];
            
            // If no page is found, default to dashboard
            return page || 'dashboard';
        }

        const initialPage = getInitialPage();
        const initialTitle = initialPage.charAt(0).toUpperCase() + initialPage.slice(1);
        loadPage(initialPage, initialTitle, false); // Load initial page without pushing to history
        // --- END: MODIFIED SECTION ---
    });
    </script>
    </body>
</html>
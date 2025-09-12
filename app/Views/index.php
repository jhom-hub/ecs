<!DOCTYPE html>
    <html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
        <title>Environmental Check Sheet</title>
        <?= $this->include('layout/headerlinks') ?>
    </head>

    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        @media (max-width: 576px) {
            .container {
                width: 370px !important;
            }

            .ecs {
                font-size: 14pt !important;
            }
        }

        .container {
            width: 500px;
        }
    </style>

    <body class="d-flex align-items-center justify-content-center vh-100">
        <div class="container bg-light p-5 shadow rounded">
            <div class="title text-center mb-3">
                <img width="100" src="images/logo2.png" alt="">
                <h1 class="ecs fs-3 mb-1 fw-bold">Environmental Check Sheet</h1>
                <p>Check. Improve. Sustain.</p>
            </div>
            <form id="loginForm" method="post" action="<?= base_url('auth/login') ?>">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-1">Login</button>
            </form>
            <!-- <a href="<?= base_url('loginguest') ?>" class="btn btn-secondary w-100">Dashboard</a> -->
        </div>
        <?= $this->include('layout/footerlinks') ?>
    <script>
        $(document).ready(function () {
            $('#loginForm').on('submit', function (e) {
                e.preventDefault();
                const form = $(this);
                const submitButton = form.find('button[type="submit"]');
                const errorMessageDiv = $('#login-error-message');
                const originalButtonHtml = submitButton.html();

                submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Signing In...');
                errorMessageDiv.text('');

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            submitButton.html('<i class="fas fa-check"></i> Success!');
                            window.location.href = response.redirect;
                        }
                    },
                    error: function (xhr) {
                        const errorMsg = xhr.responseJSON?.message || 'An unknown error occurred.';
                        errorMessageDiv.text(errorMsg);
                    },
                    complete: function () {
                        if (submitButton.prop('disabled')) {
                            submitButton.prop('disabled', false).html(originalButtonHtml);
                        }
                    }
                });
            });
        });
    </script>
    </body>

</html>
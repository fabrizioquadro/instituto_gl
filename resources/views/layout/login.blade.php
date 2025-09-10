<!DOCTYPE html>

<html lang="pt-BR" class="light-style layout-wide customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('/public/template') }}/" data-template="horizontal-menu-template">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
        <title>Instituto GL - Sistema Online</title>
        <meta name="description" content="Instituto GL - Sistema Online" />
        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('/public/img/logo.png') }}" />
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap" rel="stylesheet" />
        <!-- Icons -->
        <link rel="stylesheet" href="{{ asset('/public/template/vendor/fonts/materialdesignicons.css') }}" />
        <link rel="stylesheet" href="{{ asset('/public/template/vendor/fonts/flag-icons.css') }}" />
        <!-- Menu waves for no-customizer fix -->
        <link rel="stylesheet" href="{{ asset('/public/template/vendor/libs/node-waves/node-waves.css') }}" />
        <!-- Core CSS -->
        <link rel="stylesheet" href="{{ asset('/public/template/vendor/css/rtl/core.css') }}" class="template-customizer-core-css" />
        <link rel="stylesheet" href="{{ asset('/public/template/vendor/css/rtl/theme-default.css') }}" class="template-customizer-theme-css" />
        <link rel="stylesheet" href="{{ asset('/public/template/css/demo.css') }}" />
        <!-- Vendors CSS -->
        <link rel="stylesheet" href="{{ asset('/public/template/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
        <link rel="stylesheet" href="{{ asset('/public/template/vendor/libs/typeahead-js/typeahead.css') }}" />
        <!-- Vendor -->
        <link rel="stylesheet" href="{{ asset('/public/template/vendor/libs/@form-validation/umd/styles/index.min.css') }}" />
        <!-- Page CSS -->
        <!-- Page -->
        <link rel="stylesheet" href="{{ asset('/public/template/vendor/css/pages/page-auth.css') }}" />
        <!-- Helpers -->
        <script src="{{ asset('/public/template/vendor/js/helpers.js') }}"></script>
        <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
        <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
        <script src="{{ asset('/public/template/vendor/js/template-customizer.js') }}"></script>
        <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
        <script src="{{ asset('/public/template/js/config.js') }}"></script>
    </head>
    <body>
        <!-- Content -->
        <div class="position-relative">
            <div class="authentication-wrapper authentication-basic container-p-y">
                <div class="authentication-inner py-4">
                    <!-- Login -->
                    <div class="card p-2">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mt-5">
                            <span class="app-brand-logo demo">
                                <img src="{{ asset('/public/img/logo.png') }}" height="110px" alt="">
                            </span>
                        </div>
                        <!-- /Logo -->
                        <div class="card-body mt-2">
                            <h4 class="mb-2">Bem Vindo! 👋</h4>
                            @yield('conteudo')
                        </div>
                    </div>
                    <!-- /Login -->
                    <img alt="mask" src="../../assets/img/illustrations/auth-basic-login-mask-light.png" class="authentication-image d-none d-lg-block" data-app-light-img="illustrations/auth-basic-login-mask-light.png" data-app-dark-img="illustrations/auth-basic-login-mask-dark.png" />
                </div>
            </div>
        </div>
        <!-- / Content -->
        <!-- Core JS -->
        <!-- build:js assets/vendor/js/core.js -->
        <script src="{{ asset('/public/template/vendor/libs/jquery/jquery.js') }}"></script>
        <script src="{{ asset('/public/template/vendor/libs/popper/popper.js') }}"></script>
        <script src="{{ asset('/public/template/vendor/js/bootstrap.js') }}"></script>
        <script src="{{ asset('/public/template/vendor/libs/node-waves/node-waves.js') }}"></script>
        <script src="{{ asset('/public/template/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
        <script src="{{ asset('/public/template/vendor/libs/hammer/hammer.js') }}"></script>
        <script src="{{ asset('/public/template/vendor/libs/i18n/i18n.js') }}"></script>
        <script src="{{ asset('/public/template/vendor/libs/typeahead-js/typeahead.js') }}"></script>
        <script src="{{ asset('/public/template/vendor/js/menu.js') }}"></script>
        <!-- endbuild -->
        <!-- Vendors JS -->
        <script src="{{ asset('/public/template/vendor/libs/@form-validation/umd/bundle/popular.min.js') }}"></script>
        <script src="{{ asset('/public/template/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js') }}"></script>
        <script src="{{ asset('/public/template/vendor/libs/@form-validation/umd/plugin-auto-focus/index.min.js') }}"></script>
        <!-- Main JS -->
        <script src="{{ asset('/public/template/js/main.js') }}"></script>
        <!-- Page JS -->
        <script src="{{ asset('/public/template/js/pages-auth.js') }}"></script>
        <script src="{{ asset('/public/js/script.js') }}"></script>
    </body>
</html>

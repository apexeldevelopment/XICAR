<!DOCTYPE html>
<html class="no-js css-menubar" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        .rc-anchor-light {
            background: #f3f5f1f7 !important;
            border-radius: 4px;
        }
    </style>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="bootstrap admin template">
    <meta name="author" content="">

    <title>{{ __('Reset Password') }}</title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('global/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('global/css/bootstrap-extend.min.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/css/site.min.css') }}">

    <!-- Plugins -->
    <link rel="stylesheet" href="{{ asset('global/vendor/animsition/animsition.css') }}">
    <link rel="stylesheet" href="{{ asset('global/vendor/asscrollable/asScrollable.css') }}">
    <link rel="stylesheet" href="{{ asset('global/vendor/flag-icon-css/flag-icon.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/examples/css/pages/login.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/examples/css/uikit/modals.css') }}">


    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('global/fonts/web-icons/web-icons.min.css') }}">
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Roboto:300,400,500,300italic'>

    <script src="{{ asset('global/vendor/breakpoints/breakpoints.js' ) }}"></script>
    <script>
        Breakpoints();
    </script>
    <script src='https://www.google.com/recaptcha/api.js'></script>
    <style>
        .modal-fill-in.show {
            background-color: rgb(255 255 255 / 8%);
        }
    </style>
</head>
<body class="animsition page-login layout-full page-dark" style="background-color:rgb(108 123 141);">
<!--[if lt IE 8]>
<p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade
    your browser</a> to improve your experience.</p>
<![endif]-->
<!-- Page -->
<div class="page vertical-align text-center" data-animsition-in="fade-in" data-animsition-out="fade-out">
    <div class="page-content vertical-align-middle animation-slide-top animation-duration-1">
        <div class="rounded shadow-sm p-40 mb-4 bg-white">
            <i class="icon wb-lock" style="font-size:48px;" aria-hidden="true"></i>
            <h2>{{ __('Reset Password') }}</h2>
            <form method="POST" action="{{ route('forgot.password.update') }}"
                  aria-label="{{ __('Reset Password') }}" role="form">
                @csrf
                @include("merchant.shared.errors-and-messages")
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="form-group">
                    <div class="input-group input-group-icon">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <span class="icon wb-envelope" aria-hidden="true"></span>
                            </div>
                        </div>
                        <input id="email" type="email" readonly="true"
                               class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                               name="email" value="{{ $email ?? old('email') }}" required autofocus>
                    </div>
                    @if ($errors->has('email'))
                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                    @endif
                </div>
                <div class="form-group">
                    <div class="input-group input-group-icon">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <span class="icon wb-lock" aria-hidden="true"></span>
                            </div>
                        </div>
                        <input id="password" type="password"
                               class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
                               name="password" placeholder="New Password" required>
                    </div>
                    @if ($errors->has('password'))
                        <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('password') }}</strong>
                                            </span>
                    @endif
                </div>
                <div class="form-group">
                    <div class="input-group input-group-icon">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <span class="icon wb-lock" aria-hidden="true"></span>
                            </div>
                        </div>
                        <input id="password-confirm" type="password" class="form-control"
                               name="password_confirmation" placeholder="Confirm Password" required>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">{{ __('Reset Password') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Page -->
<!-- Core  -->
<script src="{{ asset('global/vendor/babel-external-helpers/babel-external-helpers.js') }}"></script>
<script src="{{ asset('global/vendor/jquery/jquery.js') }}"></script>
<script src="{{ asset('global/vendor/popper-js/umd/popper.min.js') }}"></script>
<script src="{{ asset('global/vendor/bootstrap/bootstrap.js') }}"></script>
<script src="{{ asset('global/vendor/animsition/animsition.js') }}"></script>
<script src="{{ asset('global/vendor/asscrollbar/jquery-asScrollbar.js') }}"></script>
<script src="{{ asset('global/vendor/asscrollable/jquery-asScrollable.js') }}"></script>
<script src="{{ asset('global/vendor/ashoverscroll/jquery-asHoverScroll.js') }}"></script>

<!-- Plugins -->
<script src="{{ asset('global/vendor/screenfull/screenfull.js') }}"></script>
<!-- Scripts -->
<script src="{{ asset('global/js/Component.js') }}"></script>
<script src="{{ asset('global/js/Plugin.js') }}"></script>
<script src="{{ asset('global/js/Base.js') }}"></script>
<script src="{{ asset('global/js/Config.js') }}"></script>

<script src="{{ asset('theme/js/Section/Menubar.js') }}"></script>
<script src="{{ asset('theme/js/Section/GridMenu.js') }}"></script>
<script src="{{ asset('theme/js/Section/Sidebar.js') }}"></script>
<script src="{{ asset('theme/js/Plugin/menu.js') }}"></script>

<script src="{{ asset('global/js/config/colors.js') }}"></script>

<!-- Page -->
<script src="{{ asset('theme/js/Site.js') }}"></script>

<script>
    (function (document, window, $) {
        'use strict';

        var Site = window.Site;
        $(document).ready(function () {
            Site.run();
        });
    })(document, window, jQuery);
</script>
</body>
</html>

{{--        <!DOCTYPE html>--}}
{{--<html class="no-js css-menubar" lang="en">--}}
{{--<head>--}}
{{--    <meta charset="utf-8">--}}
{{--    <meta http-equiv="X-UA-Compatible" content="IE=edge">--}}
{{--    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">--}}
{{--    <meta name="description" content="bootstrap admin template">--}}
{{--    <meta name="author" content="">--}}

{{--        <title>{{ __('Reset Password') }}</title>--}}

{{--        <!-- Stylesheets -->--}}
{{--        <link rel="stylesheet" href="{{ asset('global/css/bootstrap.min.css') }}">--}}
{{--        <link rel="stylesheet" href="{{ asset('global/css/bootstrap-extend.min.css') }}">--}}
{{--        <link rel="stylesheet" href="{{ asset('theme/css/site.min.css') }}">--}}

{{--        <!-- Plugins -->--}}
{{--        <link rel="stylesheet" href="{{ asset('global/vendor/animsition/animsition.css') }}">--}}
{{--        <link rel="stylesheet" href="{{ asset('global/vendor/asscrollable/asScrollable.css') }}">--}}
{{--        <link rel="stylesheet" href="{{ asset('global/vendor/flag-icon-css/flag-icon.css') }}">--}}
{{--        <link rel="stylesheet" href="{{ asset('theme/examples/css/pages/login.css') }}">--}}
{{--        <link rel="stylesheet" href="{{ asset('theme/examples/css/uikit/modals.css') }}">--}}


{{--        <!-- Fonts -->--}}
{{--        <link rel="stylesheet" href="{{ asset('global/fonts/web-icons/web-icons.min.css') }}">--}}
{{--        <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Roboto:300,400,500,300italic'>--}}

{{--        <script src="{{ asset('global/vendor/breakpoints/breakpoints.js' ) }}"></script>--}}
{{--        <script>--}}
{{--            Breakpoints();--}}
{{--        </script>--}}
{{--        <script src='https://www.google.com/recaptcha/api.js'></script>--}}
{{--        <style>--}}
{{--            .modal-fill-in.show {--}}
{{--                background-color: rgb(255 255 255 / 8%);--}}
{{--            }--}}
{{--        </style>--}}
{{--</head>--}}
{{--<body class="animsition layout-full" style="background-color:rgb(108 123 141);;">--}}

{{--<!-- Page -->--}}
{{--<div class="page vertical-align text-center" data-animsition-in="fade-in" data-animsition-out="fade-out" >--}}
{{--    <div class="page-content vertical-align-middle animation-slide-top animation-duration-1">--}}
{{--        <div class="rounded shadow-sm p-40 mb-4 bg-white w-400">--}}
{{--            <i class="icon wb-lock" style="font-size:48px;" aria-hidden="true"></i>--}}
{{--            <h2>Reset Password</h2>--}}
{{--            <p>You can reset your password here.</p>--}}

{{--            <form method="post" role="form">--}}
{{--                <div class="form-group">--}}
{{--                    <!--<label class="form-control-label" for="inputBasicEmail">Email Address</label>-->--}}
{{--                    <input type="email" class="form-control" id="inputBasicEmail" name="inputEmail"--}}
{{--                           placeholder=" Enter Email Address" autocomplete="off" required />--}}
{{--                </div>--}}
{{--                <div class="form-group">--}}
{{--                    <!--<label class="form-control-label" for="inputBasicPassword">New Password</label>-->--}}
{{--                    <input type="password" class="form-control" id="inputBasicPassword" name="inputPassword"--}}
{{--                           placeholder="New Password" autocomplete="off" required />--}}
{{--                </div>--}}
{{--                <div class="form-group">--}}
{{--                    <!--<label class="form-control-label" for="inputBasicPassword">Confirm Password</label>-->--}}
{{--                    <input type="password" class="form-control" id="inputBasicPassword" name="inputPassword"--}}
{{--                           placeholder="Confirm Password"  autocomplete="off" required/>--}}
{{--                </div>--}}
{{--                <div class="form-group">--}}
{{--                    <button type="submit" class="btn btn-primary btn-block">Submit</button>--}}
{{--                </div>--}}
{{--            </form>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}
{{--<!-- End Page -->--}}


{{--<footer class="page-copyright page-copyright-inverse">--}}
{{--    <p>© {{ date('Y') }}. All RIGHTS RESERVED.</p>--}}
{{--</footer>--}}
{{--<!-- Core  -->--}}
{{--<script src="{{ asset('global/vendor/babel-external-helpers/babel-external-helpers.js') }}"></script>--}}
{{--<script src="{{ asset('global/vendor/jquery/jquery.js') }}"></script>--}}
{{--<script src="{{ asset('global/vendor/popper-js/umd/popper.min.js') }}"></script>--}}
{{--<script src="{{ asset('global/vendor/bootstrap/bootstrap.js') }}"></script>--}}
{{--<script src="{{ asset('global/vendor/animsition/animsition.js') }}"></script>--}}
{{--<script src="{{ asset('global/vendor/asscrollbar/jquery-asScrollbar.js') }}"></script>--}}
{{--<script src="{{ asset('global/vendor/asscrollable/jquery-asScrollable.js') }}"></script>--}}
{{--<script src="{{ asset('global/vendor/ashoverscroll/jquery-asHoverScroll.js') }}"></script>--}}

{{--<!-- Plugins -->--}}
{{--<script src="{{ asset('global/vendor/screenfull/screenfull.js') }}"></script>--}}
{{--<!-- Scripts -->--}}
{{--<script src="{{ asset('global/js/Component.js') }}"></script>--}}
{{--<script src="{{ asset('global/js/Plugin.js') }}"></script>--}}
{{--<script src="{{ asset('global/js/Base.js') }}"></script>--}}
{{--<script src="{{ asset('global/js/Config.js') }}"></script>--}}

{{--<script src="{{ asset('theme/js/Section/Menubar.js') }}"></script>--}}
{{--<script src="{{ asset('theme/js/Section/GridMenu.js') }}"></script>--}}
{{--<script src="{{ asset('theme/js/Section/Sidebar.js') }}"></script>--}}
{{--<script src="{{ asset('theme/js/Plugin/menu.js') }}"></script>--}}

{{--<script src="{{ asset('global/js/config/colors.js') }}"></script>--}}

{{--<!-- Page -->--}}
{{--<script src="{{ asset('theme/js/Site.js') }}"></script>--}}

{{--<script>--}}
{{--    (function(document, window, $){--}}
{{--        'use strict';--}}

{{--        var Site = window.Site;--}}
{{--        $(document).ready(function(){--}}
{{--            Site.run();--}}
{{--        });--}}
{{--    })(document, window, jQuery);--}}
{{--</script>--}}
{{--</body>--}}
{{--</html>--}}
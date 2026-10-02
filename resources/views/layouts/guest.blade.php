<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ config('app.name', 'Laravel E-Commerce Store') }}</title>
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}" />
        
        <link rel="stylesheet" href="{{ asset('fonts/DMSans-Medium.woff2') }}" />
        <link rel="stylesheet" href="{{ asset('css/libs/fontawesome-icons.css') }}" />
        <link rel="stylesheet" href="{{ asset('css/libs/bootstrap-icons.css') }}" />
        <link rel="stylesheet" href="{{ asset('css/libs/themify-icons.css') }}" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link id="skin-default" rel="stylesheet" href="{{ asset('css/theme.css') }}" />
    </head>
    <body class="nk-body bg-white npc-general pg-auth no-touch nk-nio-theme ui-shady">
        <div class="nk-app-root">
            <div class="nk-main">
                <div class="nk-wrap nk-wrap-nosidebar">
                    <div class="nk-content ">
                        
                        <!-- <div class="nk-block nk-block-middle nk-auth-body wide-xs">
                            <div class="brand-logo pb-4 text-center">
                                <a href="{{ url('/') }}" class="logo-link">
                                    <img class="logo-light logo-img logo-img-lg" src="{{ asset('images/logo.png') }}" srcset="{{ asset('images/logo2x.png') }} 2x" alt="logo" />
                                    <img class="logo-dark logo-img logo-img-lg" src="{{ asset('images/logo-dark.png') }}" srcset="{{ asset('images/logo-dark2x.png') }} 2x" alt="logo-dark" />
                                </a>
                            </div>
                            
                            <a href="{{ url('/') }}" class="auth-logo">E-Commerce Store</a>
                            <div class="auth-card">
                                @yield('content')
                            </div>
                        </div> -->

                        <div class="nk-split nk-split-page nk-split-lg">
                            <div class="nk-split-content nk-block-area nk-block-area-column nk-auth-container bg-white">
                                
                                <div class="absolute-top-right d-lg-none p-3 p-sm-5">
                                    <a href="#" class="toggle btn-white btn btn-icon btn-light" data-target="athPromo">
                                        <em class="icon ni ni-info"></em>
                                    </a>
                                </div>
                                
                                <div class="nk-block nk-block-middle nk-auth-body">
                                    <!-- <div class="brand-logo pb-5">
                                        <a href="{{ url('/') }}" class="logo-link">
                                            <img class="logo-light logo-img logo-img-lg" src="{{ asset('images/logo.png') }}" srcset="{{ asset('images/logo2x.png') }} 2x" alt="logo" />
                                            <img class="logo-dark logo-img logo-img-lg" src="{{ asset('images/logo-dark.png') }}" srcset="{{ asset('images/logo-dark2x.png') }} 2x" alt="logo-dark" />
                                        </a>
                                    </div> -->
                                    <div class="nk-block-head">
                                        <div class="nk-block-head-content">
                                            <h5 class="nk-block-title">Sign In</h5>
                                            <div class="nk-block-des">
                                                <p>Welcome back. Access the Dashoard using your email and password!</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <form action="#" class="form-validate is-alter" autocomplete="off" novalidate="novalidate">
                                        <div class="form-group">
                                            <div class="form-label-group">
                                                <label class="form-label" for="email-address">Email or Username</label>
                                            </div>
                                            <div class="form-control-wrap">
                                                <input autocomplete="off" type="text" class="form-control form-control-lg" required="" id="email-address" placeholder="Enter your email address or username"/>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="form-label-group">
                                                <label class="form-label" for="password">Password</label>
                                                <a
                                                    class="link link-primary link-sm"
                                                    tabindex="-1"
                                                    href="/demo2/pages/auths/auth-reset.html"
                                                    >Forgot Password?</a>
                                            </div>
                                            <div class="form-control-wrap">
                                                <a
                                                    tabindex="-1"
                                                    href="#"
                                                    class="form-icon form-icon-right Password-switch lg"
                                                    data-target="password"
                                                    ><em class="Password-icon icon-show icon ni ni-eye"></em
                                                    ><em class="Password-icon icon-hide icon ni ni-eye-off"></em></a
                                                ><input
                                                    autocomplete="new-password"
                                                    type="password"
                                                    class="form-control form-control-lg"
                                                    required=""
                                                    id="password"
                                                    placeholder="Enter your Password"
                                                />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <button class="btn btn-lg btn-primary btn-block">Sign in</button>
                                        </div>
                                    </form>
                                    
                                    <div class="form-note-s2 pt-4">
                                        New on our platform?
                                        <a href="/demo2/pages/auths/auth-register.html">Create an account</a>
                                    </div>
                                    <div class="text-center pt-4 pb-3">
                                        <h6 class="overline-title overline-title-sap"><span>OR</span></h6>
                                    </div>
                                    <ul class="nav justify-center gx-4">
                                        <li class="nav-item">
                                            <a class="link link-primary fw-normal py-2 px-3" href="#">Facebook</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="link link-primary fw-normal py-2 px-3" href="#">Google</a>
                                        </li>
                                    </ul>
                                </div>
                                
                                {{-- Footer --}}
                                <div class="nk-block nk-auth-footer">
                                    <div class="nk-block-between">
                                        <ul class="nav nav-sm">
                                            <li class="nav-item">
                                                <a class="link link-primary fw-normal py-2 px-3" href="#">Terms &amp; Condition</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="link link-primary fw-normal py-2 px-3" href="#">Privacy Policy</a
                                                >
                                            </li>
                                            <li class="nav-item">
                                                <a class="link link-primary fw-normal py-2 px-3" href="#">Help</a>
                                            </li>
                                            <li class="nav-item dropup">
                                                <a
                                                    class="dropdown-toggle dropdown-indicator has-indicator link link-primary fw-normal py-2 px-3"
                                                    data-bs-toggle="dropdown"
                                                    data-offset="0,10"
                                                    ><small>English</small></a
                                                >
                                                <div class="dropdown-menu dropdown-menu-sm dropdown-menu-end">
                                                    <ul class="language-list">
                                                        <li>
                                                            <a href="#" class="language-item">
                                                                <img
                                                                    src="/demo2/images/flags/english.png"
                                                                    alt=""
                                                                    class="language-flag"
                                                                />
                                                                <span class="language-name">English</span></a
                                                            >
                                                        </li>
                                                        <li>
                                                            <a href="#" class="language-item">
                                                                <img
                                                                    src="/demo2/images/flags/spanish.png"
                                                                    alt=""
                                                                    class="language-flag"
                                                                /><span class="language-name">Español</span></a
                                                            >
                                                        </li>
                                                        <li>
                                                            <a href="#" class="language-item">
                                                                <img
                                                                    src="/demo2/images/flags/french.png"
                                                                    alt=""
                                                                    class="language-flag"
                                                                />
                                                                <span class="language-name">Français</span></a
                                                            >
                                                        </li>
                                                        <li>
                                                            <a href="#" class="language-item">
                                                                <img
                                                                    src="/demo2/images/flags/turkey.png"
                                                                    alt=""
                                                                    class="language-flag"
                                                                /><span class="language-name">Türkçe</span></a
                                                            >
                                                        </li>
                                                    </ul>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="mt-3">
                                        <p>© 2024 DashLite. All Rights Reserved.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="nk-split-content nk-split-stretch bg-lighter d-flex toggle-break-lg toggle-slide toggle-slide-right toggle-screen-lg" data-toggle-body="true" data-content="athPromo" data-toggle-screen="lg" data-toggle-overlay="true">
                                <div class="slider-wrap w-100 w-max-550px p-3 p-sm-5 m-auto">
                                    <div
                                        class="slider-init slick-initialized slick-slider slick-dotted"
                                        data-slick='{"dots":true, "arrows":false}'
                                    >
                                        <div class="slick-list draggable">
                                            <div class="slick-track" style="opacity: 1;width: 3234px;transform: translate3d(-462px, 0px, 0px);">
                                                <div
                                                    class="slider-item slick-slide slick-cloned"
                                                    data-slick-index="-1"
                                                    id=""
                                                    aria-hidden="true"
                                                    style="width: 462px"
                                                    tabindex="-1"
                                                >
                                                    <div class="nk-feature nk-feature-center">
                                                        <div class="nk-feature-img">
                                                            <img
                                                                class="round"
                                                                src="/demo2/images/slides/promo-c.png"
                                                                srcset="/demo2/images/slides/promo-c2x.png 2x"
                                                                alt=""
                                                            />
                                                        </div>
                                                        <div class="nk-feature-content py-4 p-sm-5">
                                                            <h4>Dashlite</h4>
                                                            <p>
                                                                You can start to create your products easily with its
                                                                user-friendly design &amp; most completed responsive
                                                                layout.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="slider-item slick-slide slick-current slick-active"
                                                    data-slick-index="0"
                                                    aria-hidden="false"
                                                    style="width: 462px"
                                                    tabindex="0"
                                                    role="tabpanel"
                                                    id="slick-slide00"
                                                    aria-describedby="slick-slide-control00"
                                                >
                                                    <div class="nk-feature nk-feature-center">
                                                        <div class="nk-feature-img">
                                                            <img
                                                                class="round"
                                                                src="/demo2/images/slides/promo-a.png"
                                                                srcset="/demo2/images/slides/promo-a2x.png 2x"
                                                                alt=""
                                                            />
                                                        </div>
                                                        <div class="nk-feature-content py-4 p-sm-5">
                                                            <h4>Dashlite</h4>
                                                            <p>
                                                                You can start to create your products easily with its
                                                                user-friendly design &amp; most completed responsive
                                                                layout.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="slider-item slick-slide"
                                                    data-slick-index="1"
                                                    aria-hidden="true"
                                                    style="width: 462px"
                                                    tabindex="-1"
                                                    role="tabpanel"
                                                    id="slick-slide01"
                                                    aria-describedby="slick-slide-control01"
                                                >
                                                    <div class="nk-feature nk-feature-center">
                                                        <div class="nk-feature-img">
                                                            <img
                                                                class="round"
                                                                src="/demo2/images/slides/promo-b.png"
                                                                srcset="/demo2/images/slides/promo-b2x.png 2x"
                                                                alt=""
                                                            />
                                                        </div>
                                                        <div class="nk-feature-content py-4 p-sm-5">
                                                            <h4>Dashlite</h4>
                                                            <p>
                                                                You can start to create your products easily with its
                                                                user-friendly design &amp; most completed responsive
                                                                layout.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="slider-item slick-slide"
                                                    data-slick-index="2"
                                                    aria-hidden="true"
                                                    style="width: 462px"
                                                    tabindex="-1"
                                                    role="tabpanel"
                                                    id="slick-slide02"
                                                    aria-describedby="slick-slide-control02"
                                                >
                                                    <div class="nk-feature nk-feature-center">
                                                        <div class="nk-feature-img">
                                                            <img
                                                                class="round"
                                                                src="/demo2/images/slides/promo-c.png"
                                                                srcset="/demo2/images/slides/promo-c2x.png 2x"
                                                                alt=""
                                                            />
                                                        </div>
                                                        <div class="nk-feature-content py-4 p-sm-5">
                                                            <h4>Dashlite</h4>
                                                            <p>
                                                                You can start to create your products easily with its
                                                                user-friendly design &amp; most completed responsive
                                                                layout.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="slider-item slick-slide slick-cloned"
                                                    data-slick-index="3"
                                                    id=""
                                                    aria-hidden="true"
                                                    style="width: 462px"
                                                    tabindex="-1"
                                                >
                                                    <div class="nk-feature nk-feature-center">
                                                        <div class="nk-feature-img">
                                                            <img
                                                                class="round"
                                                                src="/demo2/images/slides/promo-a.png"
                                                                srcset="/demo2/images/slides/promo-a2x.png 2x"
                                                                alt=""
                                                            />
                                                        </div>
                                                        <div class="nk-feature-content py-4 p-sm-5">
                                                            <h4>Dashlite</h4>
                                                            <p>
                                                                You can start to create your products easily with its
                                                                user-friendly design &amp; most completed responsive
                                                                layout.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="slider-item slick-slide slick-cloned"
                                                    data-slick-index="4"
                                                    id=""
                                                    aria-hidden="true"
                                                    style="width: 462px"
                                                    tabindex="-1"
                                                >
                                                    <div class="nk-feature nk-feature-center">
                                                        <div class="nk-feature-img">
                                                            <img
                                                                class="round"
                                                                src="/demo2/images/slides/promo-b.png"
                                                                srcset="/demo2/images/slides/promo-b2x.png 2x"
                                                                alt=""
                                                            />
                                                        </div>
                                                        <div class="nk-feature-content py-4 p-sm-5">
                                                            <h4>Dashlite</h4>
                                                            <p>
                                                                You can start to create your products easily with its
                                                                user-friendly design &amp; most completed responsive
                                                                layout.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="slider-item slick-slide slick-cloned"
                                                    data-slick-index="5"
                                                    id=""
                                                    aria-hidden="true"
                                                    style="width: 462px"
                                                    tabindex="-1"
                                                >
                                                    <div class="nk-feature nk-feature-center">
                                                        <div class="nk-feature-img">
                                                            <img
                                                                class="round"
                                                                src="/demo2/images/slides/promo-c.png"
                                                                srcset="/demo2/images/slides/promo-c2x.png 2x"
                                                                alt=""
                                                            />
                                                        </div>
                                                        <div class="nk-feature-content py-4 p-sm-5">
                                                            <h4>Dashlite</h4>
                                                            <p>
                                                                You can start to create your products easily with its
                                                                user-friendly design &amp; most completed responsive
                                                                layout.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <ul class="slick-dots" style="" role="tablist">
                                            <li class="slick-active" role="presentation">
                                                <button
                                                    type="button"
                                                    role="tab"
                                                    id="slick-slide-control00"
                                                    aria-controls="slick-slide00"
                                                    aria-label="1 of 3"
                                                    tabindex="0"
                                                    aria-selected="true"
                                                >
                                                    1
                                                </button>
                                            </li>
                                            <li role="presentation">
                                                <button
                                                    type="button"
                                                    role="tab"
                                                    id="slick-slide-control01"
                                                    aria-controls="slick-slide01"
                                                    aria-label="2 of 3"
                                                    tabindex="-1"
                                                >
                                                    2
                                                </button>
                                            </li>
                                            <li role="presentation">
                                                <button
                                                    type="button"
                                                    role="tab"
                                                    id="slick-slide-control02"
                                                    aria-controls="slick-slide02"
                                                    aria-label="3 of 3"
                                                    tabindex="-1"
                                                >
                                                    3
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="slider-dots"></div>
                                    <div class="slider-arrows"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>

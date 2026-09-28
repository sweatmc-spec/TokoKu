<div id="kt_header" class="header align-items-stretch">
    <!--begin::Container-->
    <div class="container-fluid d-flex align-items-stretch justify-content-between">

        <!--begin::Aside mobile toggle-->
        <div class="d-flex align-items-center d-lg-none ms-n4 me-1" title="Show aside menu">
            <div class="btn btn-icon btn-active-color-white" id="kt_aside_mobile_toggle">
                <i class="ki-outline ki-burger-menu fs-1"></i>
            </div>
        </div>
        <!--end::Aside mobile toggle-->

        <!--begin::Mobile logo-->
        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
            <a href="{{ url('/') }}" class="d-lg-none">
                <img alt="Logo" src="{{ asset('assets/media/logos/demo13-small.svg') }}" class="h-25px" />
            </a>
        </div>
        <!--end::Mobile logo-->

        <!--begin::Wrapper-->
        <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1">

            {{--
                Nav kiri sengaja dikosongkan: sidebar utama (aside.blade.php)
                sudah menyediakan navigasi lengkap. Tambahkan link di sini
                hanya jika memang butuh nav sekunder di atas.
            --}}
            <div class="d-flex align-items-stretch" id="kt_header_nav"></div>

            <!--begin::Toolbar-->
            <div class="topbar d-flex align-items-stretch flex-shrink-0">

                <!--begin::Theme mode toggle-->
                <div class="d-flex align-items-center">
                    <a href="#" class="topbar-item px-3 px-lg-4" data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                       data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-night-day theme-light-show fs-1"></i>
                        <i class="ki-outline ki-moon theme-dark-show fs-1"></i>
                    </a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px"
                         data-kt-menu="true" data-kt-element="theme-mode-menu">
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                <span class="menu-icon" data-kt-element="icon"><i class="ki-outline ki-night-day fs-2"></i></span>
                                <span class="menu-title">Light</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                <span class="menu-icon" data-kt-element="icon"><i class="ki-outline ki-moon fs-2"></i></span>
                                <span class="menu-title">Dark</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                <span class="menu-icon" data-kt-element="icon"><i class="ki-outline ki-screen fs-2"></i></span>
                                <span class="menu-title">System</span>
                            </a>
                        </div>
                    </div>
                </div>
                <!--end::Theme mode toggle-->

                <!--begin::User menu-->
                @auth
                    <div class="d-flex align-items-stretch" id="kt_header_user_menu_toggle">
                        <div class="topbar-item cursor-pointer symbol px-3 px-lg-5 me-n3 me-lg-n5 symbol-30px symbol-md-35px"
                             data-kt-menu-trigger="click" data-kt-menu-attach="parent"
                             data-kt-menu-placement="bottom-end" data-kt-menu-flip="bottom">
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" />
                        </div>

                        <!--begin::User account menu-->
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
                             data-kt-menu="true">

                            <div class="menu-item px-3">
                                <div class="menu-content d-flex align-items-center px-3">
                                    <div class="symbol symbol-50px me-5">
                                        <img alt="{{ auth()->user()->name }}" src="{{ auth()->user()->avatar_url }}" />
                                    </div>
                                    <div class="d-flex flex-column">
                                        <div class="fw-bold d-flex align-items-center fs-5">
                                            {{ auth()->user()->name }}
                                        </div>
                                        <span class="fw-semibold text-muted text-hover-primary fs-7">
                                            {{ auth()->user()->email }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="separator my-2"></div>

                            <div class="menu-item px-5">
                                <a href="{{ route('profile.edit') }}" class="menu-link px-5">My Profile</a>
                            </div>

                            <div class="menu-item px-5">
                                <a href="#" class="menu-link px-5"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Sign Out
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </div>
                        <!--end::User account menu-->
                    </div>
                @endauth
                <!--end::User menu-->

                <!--begin::Header menu toggle-->
                <div class="d-flex align-items-stretch d-lg-none px-3 me-n3" title="Show header menu">
                    <div class="topbar-item" id="kt_header_menu_mobile_toggle">
                        <i class="ki-outline ki-burger-menu-2 fs-1"></i>
                    </div>
                </div>
                <!--end::Header menu toggle-->

            </div>
            <!--end::Toolbar-->
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::Container-->
</div>
<!--end::Header-->
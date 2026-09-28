<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<!--begin::Head-->
<head>
	<title>@yield('title', config('app.name', 'TokoKu')) | @yield('page-title', 'dashboard')</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}" />

	<!--begin::Fonts(mandatory for all pages)-->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
	<!--end::Fonts-->

	<!--begin::Vendor Stylesheets(page-specific, pushed from child views via @push('styles'))-->
	@stack('styles')
	<!--end::Vendor Stylesheets-->

	<!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
	<link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('assets/css/custom-theme.css') }}" rel="stylesheet" type="text/css" />
	<!--end::Global Stylesheets Bundle-->

	<script>// Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }</script>
</head>
<!--end::Head-->
<!--begin::Body-->
<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed toolbar-tablet-and-mobile-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	<!--begin::Theme mode setup on page load-->
	<script>var defaultThemeMode = "light"; var themeMode; if ( document.documentElement ) { if ( document.documentElement.hasAttribute("data-bs-theme-mode")) { themeMode = document.documentElement.getAttribute("data-bs-theme-mode"); } else { if ( localStorage.getItem("data-bs-theme") !== null ) { themeMode = localStorage.getItem("data-bs-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-bs-theme", themeMode); }</script>
	<!--end::Theme mode setup on page load-->
	<!--begin::Main-->
	<!--begin::Root-->
	<div class="d-flex flex-column flex-root">
		<!--begin::Page-->
		<div class="page d-flex flex-row flex-column-fluid">

			{{-- Sidebar / left navigation menu --}}
			@include('partials.aside')

			<!--begin::Wrapper-->
			<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">

				{{-- Top header bar (search, notifications, theme switch, user menu) --}}
				@include('partials.header')

				<!--begin::Content-->
				<div class="content d-flex flex-column flex-column-fluid" id="kt_content">

					{{-- Toolbar: page title + breadcrumbs, overridden per-page via @section('page-title') / @section('breadcrumbs') --}}
					@include('partials.toolbar')

					<!--begin::Post-->
					<div class="post d-flex flex-column-fluid" id="kt_post">
						<!--begin::Container-->
						<div id="kt_content_container" class="container-xxl">
							@yield('content')
						</div>
						<!--end::Container-->
					</div>
					<!--end::Post-->
				</div>
				<!--end::Content-->

				{{-- Footer --}}
				@include('partials.footer')
			</div>
			<!--end::Wrapper-->
		</div>
		<!--end::Page-->
	</div>
	<!--end::Root-->

	{{--
		NOTE: The original template also ships drawers (Activities, Chat) and several
		modals (Create app, Upgrade plan, Search users, etc.) in this spot, right
		before the closing </body>. They were left out of this starter layout to keep
		it lean. If a page you convert needs one of them, copy the matching
		<div class="modal ..."> or <div id="kt_..." data-kt-drawer="true"> block from
		the original demo13/*.html file into a partial (e.g. partials/modals.blade.php),
		run it through the same asset()/url() fix described in INSTRUCTIONS.md, and
		@include it here.
	--}}

	<!--begin::Javascript-->
	<!--begin::Global Javascript Bundle(mandatory for all pages)-->
	<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
	<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
	<!--end::Global Javascript Bundle-->

	<!--begin::Vendor Javascript(page-specific, pushed from child views via @push('scripts'))-->
	@stack('scripts')
	<!--end::Vendor Javascript-->
	<!--end::Javascript-->
</body>
<!--end::Body-->
</html>

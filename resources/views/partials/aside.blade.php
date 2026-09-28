<div id="kt_aside" class="aside aside-dark aside-hoverable" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_mobile_toggle">
	<!--begin::Brand-->
	<div class="aside-logo flex-column-auto" id="kt_aside_logo">
		<a class="d-flex align-items-center brand-logo-wrap">
			<span class="brand-badge">T</span>
			<span class="fs-2 fw-bold logo d-flex align-items-center">
				<span class="brand-text-dark">Toko</span><span class="brand-text-accent">Ku</span>
			</span>
		</a>
		<div id="kt_aside_toggle" class="btn btn-icon w-auto px-0 btn-active-color-primary aside-toggle me-n2" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="aside-minimize">
			<i class="ki-outline ki-double-left fs-1 rotate-180"></i>
		</div>
	</div>
	<!--end::Brand-->

	<!--begin::Aside menu-->
	<div class="aside-menu flex-column-fluid">
		<div class="hover-scroll-overlay-y" id="kt_aside_menu_wrapper" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_footer" data-kt-scroll-wrappers="#kt_aside_menu" data-kt-scroll-offset="0">
			<div class="menu menu-column menu-title-gray-800 menu-state-title-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500" id="#kt_aside_menu" data-kt-menu="true">

				@php
					$rootModules = \App\Models\Module::whereNull('parent_id')->with('children.children')->orderBy('order')->get();
				@endphp

				@foreach ($rootModules as $module)
					@include('partials.menu-item', ['module' => $module])
				@endforeach

			</div>
		</div>
	</div>
	<!--end::Aside menu-->
</div>
<!--end::Aside-->
     <div class="footer py-4 d-flex flex-lg-column" id="kt_footer">
         <!--begin::Container-->
         <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between">
             <!--begin::Copyright-->
             <div class="text-gray-900 order-2 order-md-1">
                 <span class="text-muted fw-semibold me-1">{{ date('Y') }}&copy;</span>
                 <a href="{{ url('/') }}"
                     class="text-gray-800 text-hover-primary">{{ config('app.name', 'Your Company') }}</a>
             </div>
             <!--end::Copyright-->
             <!--begin::Menu-->
                 <ul class="menu menu-gray-600 menu-hover-primary fw-semibold order-1">
                     <li class="menu-item">
                         <a href="#" class="menu-link px-2">About</a>
                     </li>
                     <li class="menu-item">
                         <a href="#" class="menu-link px-2">Support</a>
                     </li>
                 </ul>
                 <!--end::Menu-->
         </div>
         <!--end::Container-->
     </div>

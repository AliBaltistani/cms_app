<aside class="app-sidebar sticky" id="sidebar">

	<!-- Start::main-sidebar-header -->
	<div class="main-sidebar-header">
		<a href="{{route('admin.dashboard')}}" class="header-logo">
			<img src="{{asset('build/assets/images/brand-logos/fav-icon.png')}}" alt="logo" class="desktop-logo">
			<img src="{{asset('build/assets/images/brand-logos/fav-icon.png')}}" alt="logo" class="toggle-dark">
			<img src="{{asset('build/assets/images/brand-logos/fav-icon.png')}}" alt="logo" class="desktop-dark">
			<img src="{{asset('build/assets/images/brand-logos/fav-icon.png')}}" alt="logo" class="toggle-logo">
		</a>
	</div>
	<!-- End::main-sidebar-header -->

	<!-- Start::main-sidebar -->
	<div class="main-sidebar" id="sidebar-scroll">

		<!-- Start::nav -->
		<nav class="main-menu-container nav nav-pills flex-column sub-open">
			<div class="slide-left" id="slide-left">
				<svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
			</div>
			<ul class="main-menu">
				<!-- Start::slide__category -->
				<li class="slide__category"><span class="category-name">Main</span></li>
				<!-- End::slide__category -->

				<!-- Start::slide -->
				<li class="slide {{ request()->is('admin/dashboard') ? 'active' : '' }}">
					<a href="{{route('admin.dashboard')}}" class="side-menu__item">
						<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M133.66,34.34a8,8,0,0,0-11.32,0L40,116.69V216h64V152h48v64h64V116.69Z" opacity="0.2"/><line x1="16" y1="216" x2="240" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="152 216 152 152 104 152 104 216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="116.69" x2="40" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="216" y1="216" x2="216" y2="116.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M24,132.69l98.34-98.35a8,8,0,0,1,11.32,0L232,132.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
						<span class="side-menu__label">Dashboard</span>
					</a>
				</li>
				<!-- End::slide -->

				<!-- Start::slide User Management -->
				<li class="slide has-sub {{ request()->is('admin/users*') || request()->is('admin/trainers*') || request()->is('admin/trainees*') ? 'open' : '' }}">
					<a href="javascript:void(0);" class="side-menu__item">
						<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="120" r="40" opacity="0.2"/><circle cx="128" cy="120" r="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M63.8,199.37a72,72,0,0,1,128.4,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M198.1,197.85A96,96,0,1,0,57.9,58.15" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="200" cy="56" r="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
						<span class="side-menu__label">User Management</span>
						<i class="ri-arrow-right-s-line side-menu__angle"></i>
					</a>
					<ul class="slide-menu child1">
						<li class="slide side-menu__label1">
							<a href="javascript:void(0)">User Management</a>
						</li>
						<li class="slide {{ request()->is('admin/users*') ? 'active' : '' }}">
							<a href="{{route('admin.users.index')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="120" r="40" opacity="0.2"/><circle cx="128" cy="120" r="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M63.8,199.37a72,72,0,0,1,128.4,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M198.1,197.85A96,96,0,1,0,57.9,58.15" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="200" cy="56" r="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								All Users</a>
						</li>
						<li class="slide {{ request()->is('admin/trainers*') ? 'active' : '' }}">
							<a href="{{route('admin.trainers.index')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M104,128a24,24,0,1,1,24,24A24,24,0,0,1,104,128Z" opacity="0.2"/><circle cx="128" cy="128" r="24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m104,128-48,48a24,24,0,0,1-34-34l48-48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m152,128,48-48a24,24,0,0,1,34,34l-48,48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Trainers</a>
						</li>
						<li class="slide {{ request()->is('admin/trainees*') ? 'active' : '' }}">
							<a href="{{route('admin.trainees.index')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="120" r="40" opacity="0.2"/><circle cx="128" cy="120" r="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M63.8,199.37a72,72,0,0,1,128.4,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M198.1,197.85A96,96,0,1,0,57.9,58.15" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="200" cy="56" r="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Trainees</a>
						</li>
					</ul>
				</li>
				<!-- End::slide User Management -->

				<!-- Start::slide Specializations Management -->
				<li class="slide has-sub {{ request()->is('admin/specializations*') ? 'open' : '' }}">
					<a href="javascript:void(0);" class="side-menu__item">
						<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z" opacity="0.2"/><circle cx="128" cy="128" r="104" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="128 80 128 128 168 168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="128" r="12" fill="currentColor"/></svg>
						<span class="side-menu__label">Specializations</span>
						<i class="ri-arrow-right-s-line side-menu__angle"></i>
					</a>
					<ul class="slide-menu child1">
						<li class="slide side-menu__label1">
							<a href="javascript:void(0)">Specializations</a>
						</li>
						<li class="slide {{ request()->is('admin/specializations') && !request()->is('admin/specializations/create') ? 'active' : '' }}">
							<a href="{{route('admin.specializations.index')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z" opacity="0.2"/><circle cx="128" cy="128" r="104" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="128 80 128 128 168 168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="128" r="12" fill="currentColor"/></svg>
								All Specializations</a>
						</li>
						<li class="slide {{ request()->is('admin/specializations/create') ? 'active' : '' }}">
							<a href="{{route('admin.specializations.create')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><line x1="40" y1="128" x2="216" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="40" x2="128" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Add New Specialization</a>
						</li>
					</ul>
				</li>
				<!-- End::slide Specializations Management -->

				<!-- Start::slide Goals Management -->
				<li class="slide has-sub {{ request()->is('admin/goals*') ? 'open' : '' }}">
					<a href="javascript:void(0);" class="side-menu__item">
						<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="40" opacity="0.2"/><circle cx="128" cy="128" r="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m70.7,185.3,33.6-33.6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m151.7,104.3,33.6-33.6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="208" y1="32" x2="224" y2="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="208" y1="80" x2="224" y2="64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="208" x2="48" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="176" x2="48" y2="192" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
						<span class="side-menu__label">Goals Management</span>
						<i class="ri-arrow-right-s-line side-menu__angle"></i>
					</a>
					<ul class="slide-menu child1">
						<li class="slide side-menu__label1">
							<a href="javascript:void(0)">Goals Management</a>
						</li>
						<li class="slide {{ request()->is('admin/goals*') ? 'active' : '' }}">
							<a href="{{route('goals.index')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="40" opacity="0.2"/><circle cx="128" cy="128" r="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m70.7,185.3,33.6-33.6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m151.7,104.3,33.6-33.6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="208" y1="32" x2="224" y2="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="208" y1="80" x2="224" y2="64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="208" x2="48" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="176" x2="48" y2="192" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								All Goals</a>
						</li>
						<li class="slide {{ request()->is('admin/goals/create') ? 'active' : '' }}">
							<a href="{{route('goals.create')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><line x1="40" y1="128" x2="216" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="40" x2="128" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Add New Goal</a>
						</li>
					</ul>
				</li>
				<!-- End::slide Goals Management -->

				<!-- Start::slide Workouts Management -->
				<li class="slide has-sub {{ request()->is('admin/workouts*') || request()->is('admin/programs*') || request()->is('admin/program-builder*') ? 'open' : '' }}">
					<a href="javascript:void(0);" class="side-menu__item">
						<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" width="23" height="23" viewBox="0 0 37 37" fill="none">
							<g clip-path="url(#clip0_172_3513)">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M35.6235 17.1493H34.4811V12.58C34.4811 11.3183 33.4583 10.2954 32.1965 10.2954H29.9119V9.15311C29.9119 7.89135 28.8891 6.86849 27.6273 6.86849H24.2004C22.9386 6.86849 21.9158 7.89135 21.9158 9.15311V17.1493H15.0619V9.15311C15.0619 7.89135 14.0391 6.86849 12.7773 6.86849H9.35038C8.08862 6.86849 7.06576 7.89135 7.06576 9.15311V10.2954H4.78114C3.51939 10.2954 2.49653 11.3183 2.49653 12.58V17.1493H1.35422C0.723343 17.1493 0.211914 17.6607 0.211914 18.2916C0.211914 18.9225 0.723343 19.4339 1.35422 19.4339H2.49653V24.0031C2.49653 25.2649 3.51939 26.2877 4.78114 26.2877H7.06576V27.43C7.06576 28.6918 8.08862 29.7146 9.35038 29.7146H12.7773C14.0391 29.7146 15.0619 28.6918 15.0619 27.43V19.4339H21.9158V27.43C21.9158 28.6918 22.9386 29.7146 24.2004 29.7146H27.6273C28.8891 29.7146 29.9119 28.6918 29.9119 27.43V26.2877H32.1965C33.4583 26.2877 34.4811 25.2649 34.4811 24.0031V19.4339H35.6235C36.2543 19.4339 36.7658 18.9225 36.7658 18.2916C36.7658 17.6607 36.2543 17.1493 35.6235 17.1493ZM4.78114 24.0031V12.58H7.06576V24.0031H4.78114ZM12.7773 27.43H9.35038V9.15311H12.7773V27.43ZM27.6273 27.43H24.2004V9.15311H27.6273V25.1197C27.6273 25.1283 27.6273 25.1369 27.6273 25.1454C27.6273 25.154 27.6273 25.1625 27.6273 25.1711V27.43ZM32.1965 24.0031H29.9119V12.58H32.1965V24.0031Z" fill="#FF6A00"/>
							</g>
							<defs>
							<clipPath id="clip0_172_3513">
							<rect width="36.5538" height="36.5538" fill="white" transform="translate(0.211914 0.0146484)"/>
							</clipPath>
							</defs>
						</svg>
						<span class="side-menu__label">Fitness Management</span>
						<i class="ri-arrow-right-s-line side-menu__angle"></i>
					</a>
					<ul class="slide-menu child1">
						<li class="slide side-menu__label1">
							<a href="javascript:void(0)">Fitness Management</a>
						</li>
						<!-- <li class="slide {{ (request()->is('admin/workouts') || request()->is('admin/workouts/*/edit') || request()->is('admin/workouts/*/show') || request()->is('admin/workouts/*')) && !request()->is('admin/workouts/create') ? 'active' : '' }}">
							<a href="{{route('workouts.index')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" width="16" height="16" viewBox="0 0 37 37" fill="none">
									<g clip-path="url(#clip0_172_3513_sub)">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M35.6235 17.1493H34.4811V12.58C34.4811 11.3183 33.4583 10.2954 32.1965 10.2954H29.9119V9.15311C29.9119 7.89135 28.8891 6.86849 27.6273 6.86849H24.2004C22.9386 6.86849 21.9158 7.89135 21.9158 9.15311V17.1493H15.0619V9.15311C15.0619 7.89135 14.0391 6.86849 12.7773 6.86849H9.35038C8.08862 6.86849 7.06576 7.89135 7.06576 9.15311V10.2954H4.78114C3.51939 10.2954 2.49653 11.3183 2.49653 12.58V17.1493H1.35422C0.723343 17.1493 0.211914 17.6607 0.211914 18.2916C0.211914 18.9225 0.723343 19.4339 1.35422 19.4339H2.49653V24.0031C2.49653 25.2649 3.51939 26.2877 4.78114 26.2877H7.06576V27.43C7.06576 28.6918 8.08862 29.7146 9.35038 29.7146H12.7773C14.0391 29.7146 15.0619 28.6918 15.0619 27.43V19.4339H21.9158V27.43C21.9158 28.6918 22.9386 29.7146 24.2004 29.7146H27.6273C28.8891 29.7146 29.9119 28.6918 29.9119 27.43V26.2877H32.1965C33.4583 26.2877 34.4811 25.2649 34.4811 24.0031V19.4339H35.6235C36.2543 19.4339 36.7658 18.9225 36.7658 18.2916C36.7658 17.6607 36.2543 17.1493 35.6235 17.1493ZM4.78114 24.0031V12.58H7.06576V24.0031H4.78114ZM12.7773 27.43H9.35038V9.15311H12.7773V27.43ZM27.6273 27.43H24.2004V9.15311H27.6273V25.1197C27.6273 25.1283 27.6273 25.1369 27.6273 25.1454C27.6273 25.154 27.6273 25.1625 27.6273 25.1711V27.43ZM32.1965 24.0031H29.9119V12.58H32.1965V24.0031Z" fill="currentColor"/>
									</g>
									<defs>
									<clipPath id="clip0_172_3513_sub">
									<rect width="36.5538" height="36.5538" fill="white" transform="translate(0.211914 0.0146484)"/>
									</clipPath>
									</defs>
								</svg>
								All Workouts</a>
						</li>
						<li class="slide {{ request()->is('admin/workouts/create') ? 'active' : '' }}">
							<a href="{{route('workouts.create')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><line x1="40" y1="128" x2="216" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="40" x2="128" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Add New Workout</a>
						</li> -->
						<li class="slide {{ (request()->is('admin/programs') || request()->is('admin/programs/*/edit') || request()->is('admin/programs/*/show') || request()->is('admin/programs/*')) && !request()->is('admin/programs/create') ? 'active' : '' }}">
							<a href="{{route('programs.index')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M208,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32Z" opacity="0.2"/><rect x="32" y="48" width="192" height="160" rx="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="80" x2="192" y2="80" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="112" x2="192" y2="112" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="144" x2="192" y2="144" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="176" x2="192" y2="176" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								All Workouts</a>
						</li>
						<li class="slide {{ request()->is('admin/programs/create') ? 'active' : '' }}">
							<a href="{{route('programs.create')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><line x1="40" y1="128" x2="216" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="40" x2="128" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Add New Workout</a>
						</li>
					</ul>
				</li>
				<!-- End::slide Workouts Management -->

				<!-- Start::slide Nutrition Management -->
				<li class="slide has-sub {{ request()->is('admin/nutrition-plans*') ? 'open' : '' }}">
					<a href="javascript:void(0);" class="side-menu__item">
						<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M208,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,184a8,8,0,0,1-16,0V72a8,8,0,0,1,16,0Zm32,0a8,8,0,0,1-16,0V72a8,8,0,0,1,16,0Zm32,0a8,8,0,0,1-16,0V72a8,8,0,0,1,16,0Zm32,0a8,8,0,0,1-16,0V72a8,8,0,0,1,16,0Zm32,0a8,8,0,0,1-16,0V72a8,8,0,0,1,16,0Z" opacity="0.2"/><rect x="32" y="48" width="192" height="160" rx="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="72" x2="64" y2="184" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="96" y1="72" x2="96" y2="184" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="72" x2="128" y2="184" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="160" y1="72" x2="160" y2="184" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="192" y1="72" x2="192" y2="184" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
						<span class="side-menu__label">Nutrition Management</span>
						<i class="ri-arrow-right-s-line side-menu__angle"></i>
					</a>
					<ul class="slide-menu child1">
						<li class="slide side-menu__label1">
							<a href="javascript:void(0)">Nutrition Management</a>
						</li>
						<li class="slide {{ request()->is('admin/nutrition-plans') && !request()->is('admin/nutrition-plans/create') ? 'active' : '' }}">
							<a href="{{route('admin.nutrition-plans.index')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M208,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,184a8,8,0,0,1-16,0V72a8,8,0,0,1,16,0Zm32,0a8,8,0,0,1-16,0V72a8,8,0,0,1,16,0Zm32,0a8,8,0,0,1-16,0V72a8,8,0,0,1,16,0Zm32,0a8,8,0,0,1-16,0V72a8,8,0,0,1,16,0Zm32,0a8,8,0,0,1-16,0V72a8,8,0,0,1,16,0Z" opacity="0.2"/><rect x="32" y="48" width="192" height="160" rx="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="72" x2="64" y2="184" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="96" y1="72" x2="96" y2="184" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="72" x2="128" y2="184" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="160" y1="72" x2="160" y2="184" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="192" y1="72" x2="192" y2="184" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								All Nutrition Plans</a>
						</li>
						<li class="slide {{ request()->is('admin/nutrition-plans/create') ? 'active' : '' }}">
							<a href="{{route('admin.nutrition-plans.create')}}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><line x1="40" y1="128" x2="216" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="40" x2="128" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Create Nutrition Plan</a>
						</li>
					</ul>
				</li>
				<!-- End::slide Nutrition Management -->


				<!-- Start::slide Scheduling & Booking -->
				<li class="slide has-sub {{ request()->is('admin/bookings*') ? 'open' : '' }}">
					<a href="javascript:void(0);" class="side-menu__item">
						<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M208,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32Z" opacity="0.2"/><rect x="32" y="48" width="192" height="160" rx="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="16" x2="128" y2="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="176" y1="16" x2="176" y2="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="80" y1="16" x2="80" y2="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="80" x2="224" y2="80" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
						<span class="side-menu__label">Scheduling & Booking</span>
						<i class="ri-arrow-right-s-line side-menu__angle"></i>
					</a>
					<ul class="slide-menu child1">
						<li class="slide side-menu__label1">
							<a href="javascript:void(0)">Scheduling & Booking</a>
						</li>
						<li class="slide {{ request()->is('admin/bookings/dashboard') ? 'active' : '' }}">
							<a href="{{ route('admin.bookings.dashboard') }}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="32" y="48" width="192" height="160" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m68,88 L188,88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="68" cy="128" r="8" fill="currentColor"/><path d="m88,128 L188,128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="68" cy="168" r="8" fill="currentColor"/><path d="m88,168 L188,168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Dashboard
							</a>
						</li>

						<li class="slide {{ request()->is('admin/trainers-scheduling*') ? 'active' : '' }}">
							<a href="{{ route('admin.trainers-scheduling.index') }}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M224,128a96,96,0,1,1-96-96A96,96,0,0,1,224,128Z" opacity="0.2"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="120" r="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m63.8,199.37a72,72,0,0,1,128.4,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="176" y1="56" x2="200" y2="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="200" y1="56" x2="176" y2="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Trainers Overview
							</a>
						</li>
						<li class="slide {{ request()->is('admin/bookings/scheduling-menu*') ? 'active' : '' }}">
							<a href="{{ route('admin.bookings.scheduling-menu') }}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m70.7,70.7,33.6,33.6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m151.7,151.7,33.6,33.6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m185.3,70.7-33.6,33.6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="m104.3,151.7-33.6,33.6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="40" x2="128" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="128" x2="56" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="200" x2="128" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="200" y1="128" x2="216" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Scheduling Settings
							</a>
						</li>
						<!-- <li class="slide {{ request()->is('admin/bookings/schedule') ? 'active' : '' }}">
							<a href="{{ route('admin.bookings.schedule') }}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M208,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32Z" opacity="0.2"/><rect x="32" y="48" width="192" height="160" rx="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="16" x2="128" y2="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="176" y1="16" x2="176" y2="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="80" y1="16" x2="80" y2="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="80" x2="224" y2="80" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Schedule Calendar
							</a>
						</li>  -->
						 <li class="slide {{ request()->is('admin/bookings') && !request()->is('admin/bookings/*') ? 'active' : '' }}">
							<a href="{{ route('admin.bookings.index') }}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="40" y="40" width="176" height="176" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="88" x2="216" y2="88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="40" x2="128" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								All Bookings
							</a>
						</li>
						<li class="slide {{ request()->is('admin/bookings/google-calendar/create') ? 'active' : '' }}">
							<a href="{{ route('admin.bookings.google-calendar') }}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><line x1="40" y1="128" x2="216" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="40" x2="128" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								Create Booking
							</a>
						</li>
						
					</ul>
				</li>
			<!-- End::slide Scheduling & Booking -->

			<!-- Start::slide Billing -->
			<li class="slide has-sub {{ request()->is('admin/billing*') ? 'open' : '' }}">
				<a href="javascript:void(0);" class="side-menu__item">
					<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M224,56H32A16,16,0,0,0,16,72V184a16,16,0,0,0,16,16H224a16,16,0,0,0,16-16V72A16,16,0,0,0,224,56Z" opacity="0.2"/><rect x="16" y="56" width="224" height="144" rx="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="16" y1="104" x2="240" y2="104" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="64" cy="80" r="8" fill="currentColor"/><circle cx="96" cy="80" r="8" fill="currentColor"/><circle cx="128" cy="80" r="8" fill="currentColor"/></svg>
					<span class="side-menu__label">Billing</span>
					<i class="ri-arrow-right-s-line side-menu__angle"></i>
				</a>
				<ul class="slide-menu child1">
					<li class="slide side-menu__label1">
						<a href="javascript:void(0)">Billing</a>
					</li>
					<li class="slide {{ request()->is('admin/billing') ? 'active' : '' }}">
						<a href="{{ route('admin.billing.index') }}" class="side-menu__item">
							<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M133.66,34.34a8,8,0,0,0-11.32,0L40,116.69V216h64V152h48v64h64V116.69Z" opacity="0.2"/><line x1="16" y1="216" x2="240" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="152 216 152 152 104 152 104 216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="116.69" x2="40" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="216" y1="216" x2="216" y2="116.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M24,132.69l98.34-98.35a8,8,0,0,1,11.32,0L232,132.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
							Dashboard
						</a>
					</li>
					<li class="slide {{ request()->is('admin/billing/invoices') ? 'active' : '' }}">
						<a href="{{ route('admin.billing.invoices') }}" class="side-menu__item">
							<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M208,32H88a16,16,0,0,0-16,16V208a16,16,0,0,0,16,16h96a16,16,0,0,0,16-16Z" opacity="0.2"/><rect x="72" y="32" width="144" height="192" rx="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="104" y1="80" x2="184" y2="80" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="104" y1="112" x2="184" y2="112" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="104" y1="144" x2="184" y2="144" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
							Invoices
						</a>
					</li>
					<li class="slide {{ request()->is('admin/billing/payouts') ? 'active' : '' }}">
						<a href="{{ route('admin.billing.payouts') }}" class="side-menu__item">
							<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M224,56H32A16,16,0,0,0,16,72V184a16,16,0,0,0,16,16H224a16,16,0,0,0,16-16V72A16,16,0,0,0,224,56Z" opacity="0.2"/><rect x="16" y="56" width="224" height="144" rx="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M104,144h48a24,24,0,0,1,0,48H104a24,24,0,0,1,0-48Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
							Payouts
						</a>
					</li>
				</ul>
			</li>
			<!-- End::slide Billing -->
				

			</ul>
			<ul class="doublemenu_bottom-menu main-menu mb-0 border-top">
				<!-- Start::slide -->
				<li class="slide">
					<a href="javascript:void(0);" class="side-menu__item layout-setting-doublemenu">
						<span class="light-layout">
							<!-- Start::header-link-icon -->
							<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z" opacity="0.2"/><path d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
							<!-- End::header-link-icon -->
						</span>
						<span class="dark-layout">
							<!-- Start::header-link-icon -->
							<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="56" opacity="0.2"/><line x1="128" y1="40" x2="128" y2="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="128" r="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="64" x2="56" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="192" x2="56" y2="200" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="192" y1="64" x2="200" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="192" y1="192" x2="200" y2="200" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="128" x2="32" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="216" x2="128" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="216" y1="128" x2="224" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
							<!-- End::header-link-icon -->
						</span>
						<span class="side-menu__label">Theme Settings</span>
					</a>
				</li>
				<!-- End::slide -->
				<!-- Start::slide -->
				<li class="slide">
					<form method="POST" action="{{ route('logout') }}" id="logout-form">
						@csrf
						<a href="javascript:void(0);" class="side-menu__item" onclick="document.getElementById('logout-form').submit();">
							<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M48,40H208a16,16,0,0,1,16,16V200a16,16,0,0,1-16,16H48a0,0,0,0,1,0,0V40A0,0,0,0,1,48,40Z" opacity="0.2"/><polyline points="112 40 48 40 48 216 112 216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="112" y1="128" x2="224" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="184 88 224 128 184 168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
							<span class="side-menu__label">Logout</span>
						</a>
					</form>
				</li>
				<!-- End::slide -->
				<!-- Start::slide -->
				<li class="slide">
					<a href="{{route('profile.index')}}" class="side-menu__item p-1 rounded-circle mb-0">
						<span class="avatar avatar-md avatar-rounded">
							@if(Auth::user()->profile_image)
								<img src="{{ asset('storage/' . Auth::user()->profile_image) }}" alt="{{ Auth::user()->name }}">
							@else
							<div class="header-link-icon avatar bg-primary-transparent avatar-rounded">
								{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
								</div>
								
							@endif
						</span>
					</a>
				</li>
				<!-- End::slide -->
			</ul>
			<div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path> </svg></div>
		</nav>
		<!-- End::nav -->

	</div>
	<!-- End::main-sidebar -->

</aside>
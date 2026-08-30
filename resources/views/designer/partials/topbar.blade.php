@php($designerUnreadCount = $unreadNotificationsCount ?? 0)

<header class='top-utility-bar' aria-label='أدوات الصفحة' data-i18n-aria='pageTools'>
    <div class='top-bar-start'>
        <button
            type='button'
            class='utility-btn sidebar-toggle-btn'
            id='sidebarToggleButton'
            aria-controls='profileSidebar'
            aria-label='إغلاق القائمة الجانبية'
            aria-expanded='true'
            data-i18n-aria='closeSidebar'
        >
            <i class='bi bi-x-lg' id='sidebarToggleIcon' aria-hidden='true'></i>
        </button>
    </div>

    <div class='top-bar-end'>
        <button
            type='button'
            class='utility-btn'
            id='themeToggleButton'
            aria-label='تفعيل الوضع الليلي'
            data-i18n-aria='switchToDark'
        >
            <i class='bi bi-moon-stars' id='themeToggleIcon' aria-hidden='true'></i>
        </button>

        <button
            type='button'
            class='utility-btn language-toggle-btn'
            id='languageToggleButton'
            aria-label='تغيير اللغة'
            data-i18n-aria='changeLanguage'
        >
            <i class='bi bi-globe2' aria-hidden='true'></i>
            <span id='languageToggleText' aria-hidden='true'>EN</span>
        </button>

        <a
            href='{{ route('designer.notifications') }}'
            class='utility-btn notif-btn'
            aria-label='الإشعارات'
            data-i18n-aria='notifications'
        >
            <i class='bi bi-bell' aria-hidden='true'></i>
            @if($designerUnreadCount > 0)
                <span class='notif-dot' aria-hidden='true'></span>
            @endif
        </a>
    </div>
</header>

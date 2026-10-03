<aside class="wallet-sidebar" id="walletSidebar" aria-label="القائمة الجانبية للمطبعة">
    <nav aria-label="روابط حساب المطبعة">
        <a href="{{ route('print-provider.dashboard') }}" @class(['active' => request()->routeIs('print-provider.dashboard')]) @if(request()->routeIs('print-provider.dashboard')) aria-current="page" @endif>
            <i class="bi bi-grid" aria-hidden="true"></i><span>لوحة التحكم</span>
        </a>
        <a href="{{ route('print-provider.profile') }}" @class(['active' => request()->routeIs('print-provider.profile')]) @if(request()->routeIs('print-provider.profile')) aria-current="page" @endif>
            <i class="bi bi-person" aria-hidden="true"></i><span>الملف الشخصي</span>
        </a>
        <a href="{{ route('print-provider.services') }}" @class(['active' => request()->routeIs('print-provider.services')]) @if(request()->routeIs('print-provider.services')) aria-current="page" @endif>
            <i class="bi bi-printer" aria-hidden="true"></i><span>خدمات الطباعة</span>
        </a>
        <a href="{{ route('print-provider.requests') }}" @class(['active' => request()->routeIs('print-provider.requests')]) @if(request()->routeIs('print-provider.requests')) aria-current="page" @endif>
            <i class="bi bi-clipboard-check" aria-hidden="true"></i><span>طلبات الطباعة</span>
        </a>
        <a href="{{ route('print-provider.earnings') }}" @class(['active' => request()->routeIs('print-provider.earnings')]) @if(request()->routeIs('print-provider.earnings')) aria-current="page" @endif>
            <i class="bi bi-wallet2" aria-hidden="true"></i><span>الأرباح والمحفظة</span>
        </a>
        <div class="wallet-sidebar-divider" aria-hidden="true"></div>
        <a href="{{ route('print-provider.settings') }}" @class(['active' => request()->routeIs('print-provider.settings*')]) @if(request()->routeIs('print-provider.settings*')) aria-current="page" @endif>
            <i class="bi bi-gear" aria-hidden="true"></i><span>الإعدادات</span>
        </a>
        <a href="{{ route('print-provider.support') }}" @class(['active' => request()->routeIs('print-provider.support*')]) @if(request()->routeIs('print-provider.support*')) aria-current="page" @endif>
            <i class="bi bi-headphones" aria-hidden="true"></i><span>مركز المساعدة</span>
        </a>
    </nav>

    <form method="POST" action="{{ route('logout') }}" id="printProviderLogoutForm">
        @csrf
        <button class="logout" id="logoutButton" type="submit"><i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>تسجيل الخروج</span></button>
    </form>
</aside>

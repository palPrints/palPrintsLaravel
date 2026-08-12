<x-guest-layout>
    <!-- ✅ رسالة النجاح -->
    @if(session('status'))
        <div class="mb-4 text-sm text-green-600 bg-green-100 p-3 rounded-lg">
            {{ session('status') }}
        </div>
    @endif

    <!-- ✅ رسالة الخطأ -->
    @if(session('error'))
        <div class="mb-4 text-sm text-red-600 bg-red-100 p-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- ✅ أخطاء التحقق -->
    @if($errors->any())
        <div class="mb-4 text-sm text-red-600 bg-red-100 p-3 rounded-lg">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mb-4 text-sm text-gray-600">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

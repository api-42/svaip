@extends('layouts.main')
@section('scripts')
<script>
document.addEventListener('load', function() {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const errorSpans = document.getElementsByClassName('text-red-500');

    function clearErrors() {
        Array.from(errorSpans).forEach(span => {
            span.style.display = 'none';
        });
    }

    emailInput.addEventListener('focus', clearErrors);
    passwordInput.addEventListener('focus', clearErrors);
});
</script>
@endsection
@section('content')
    <div class="flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="space-y-2 mb-8 bg-white p-6 rounded-lg shadow-lg">
                <p class="text-center text-lg text-gray-900 font-medium">
                    Create your Svaip account
                </p>
                <p class="pt-2 text-sm text-gray-900">
                    At Svaip you get 21 days trial period to explore and create your account.
                </p>
                <p class="pt-2 text-sm text-gray-700">
                    No credit card required, no strings attached.
                </p>
                <p class="pt-2 text-sm text-gray-700">
                    After the trial period, you can choose to upgrade to a paid plan to continue using Svaip's features and services.
                </p>
                <p class="mt-2 text-sm">
                    <a href="{{ route('pricing') }}" class="font-medium text-indigo-600 hover:text-indigo-500">See plans here.</a>
                </p>
            </div>
            <form class="mt-8 space-y-6" action="{{ route('register') }}" method="POST">
                @csrf
                <div class="rounded-md">
                    <div>
                        <label for="name" class="sr-only">Full name</label>
                        <input id="name" name="name" type="name" value="{{ old('name') ?? '' }}" autocomplete="name" required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Full name">
                    </div>
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') ?? '' }}" autocomplete="email" required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Email address ">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password (min 8 characters)</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Password">
                    </div>
                </div>
                <div class="p-2">
                    @error('email')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                    @error('password')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Register
                    </button>
                </div>
                <div class="text-sm text-center">
                    <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Already have an account? Login here.
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

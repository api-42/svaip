@extends('layouts.main')

@section('content')
    <div class="flex items-center justify-center px-4 sm:px-6 lg:px-8" x-data="{
        email: '',
        password: '',
        errors: {},
        loading: false,
        
        async handleLogin() {
            if (this.loading) return;
            
            this.errors = {};
            this.loading = true;
            
            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        email: this.email,
                        password: this.password
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '/';
                } else {
                    this.errors = data.errors || { email: [data.message] };
                }
            } catch (error) {
                this.errors = { email: ['An error occurred. Please try again.'] };
            } finally {
                this.loading = false;
            }
        }
    }">
        <div class="max-w-md w-full space-y-8">
            <h2 class="text-center text-2xl text-gray-900">
                Login to your svaip account
            </h2>
            <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
                <div class="rounded-md">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input 
                            id="email" 
                            type="email" 
                            x-model="email"
                            autocomplete="email" 
                            required
                            @focus="errors.email = []"
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Email address">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input 
                            id="password" 
                            type="password" 
                            x-model="password"
                            autocomplete="current-password" 
                            required
                            @focus="errors.password = []"
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Password">
                    </div>
                </div>
                
                <div class="p-2">
                    <template x-if="errors.email">
                        <div class="text-red-500 text-sm" x-text="errors.email[0]"></div>
                    </template>
                    <template x-if="errors.password">
                        <div class="text-red-500 text-sm" x-text="errors.password[0]"></div>
                    </template>
                </div>
                
                <div>
                    <button 
                        type="submit"
                        :disabled="loading"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                        <span x-show="!loading">Sign In</span>
                        <span x-show="loading">
                            <i class="fa-solid fa-spinner fa-spin"></i> Signing in...
                        </span>
                    </button>
                </div>
            </form>
            <div class="text-sm text-center">
                <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Don't have an account? Register here.
                </a>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.main')

@section('content')
    <div class="flex items-center justify-center px-4 sm:px-6 lg:px-8" x-data="{
        name: '',
        email: '',
        password: '',
        errors: {},
        loading: false,
        
        async handleRegister() {
            if (this.loading) return;
            
            this.errors = {};
            this.loading = true;
            
            try {
                const response = await fetch('/api/auth/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: this.name,
                        email: this.email,
                        password: this.password
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '/';
                } else {
                    this.errors = data.errors || {};
                }
            } catch (error) {
                this.errors = { email: ['An error occurred. Please try again.'] };
            } finally {
                this.loading = false;
            }
        }
    }">
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
            
            <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
                <div class="rounded-md">
                    <div>
                        <label for="name" class="sr-only">Full name</label>
                        <input 
                            id="name" 
                            type="text" 
                            x-model="name"
                            autocomplete="name" 
                            required
                            @focus="errors.name = []"
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Full name">
                    </div>
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input 
                            id="email" 
                            type="email" 
                            x-model="email"
                            autocomplete="email" 
                            required
                            @focus="errors.email = []"
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Email address">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password (min 8 characters)</label>
                        <input 
                            id="password" 
                            type="password" 
                            x-model="password"
                            autocomplete="new-password" 
                            required
                            @focus="errors.password = []"
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Password">
                    </div>
                </div>
                
                <div class="p-2">
                    <template x-if="errors.name">
                        <div class="text-red-500 text-sm mb-2" x-text="errors.name[0]"></div>
                    </template>
                    <template x-if="errors.email">
                        <div class="text-red-500 text-sm mb-2" x-text="errors.email[0]"></div>
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
                        <span x-show="!loading">Register</span>
                        <span x-show="loading">
                            <i class="fa-solid fa-spinner fa-spin"></i> Creating account...
                        </span>
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

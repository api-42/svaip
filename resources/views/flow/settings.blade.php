@extends('layouts.main')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="flowSettings()">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('flow.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm mb-2 inline-block">
                ← Back to Flows
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Flow Settings</h1>
            <p class="text-gray-600 mt-2">{{ $flow->name }}</p>
        </div>

        <!-- Public Access Section -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Public Access</h2>
                    <p class="text-sm text-gray-600 mt-1">Allow anyone to take this flow without logging in</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" 
                           x-model="isPublic" 
                           @change="togglePublic()"
                           class="sr-only peer"
                           {{ $flow->is_public ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            <!-- Public URL (shown when public) -->
            <div x-show="isPublic" x-transition class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Public URL</label>
                    <div class="flex gap-2">
                        <input type="text" 
                               readonly 
                               value="{{ $flow->publicUrl() ?? url('/p/' . ($flow->public_slug ?: 'not-set')) }}"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-700"
                               onclick="this.select()">
                        <button @click="copyToClipboard('{{ $flow->publicUrl() ?? url('/p/' . ($flow->public_slug ?: 'not-set')) }}')"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-medium transition-colors">
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                        <a href="{{ $flow->publicUrl() ?? url('/p/' . ($flow->public_slug ?: 'not-set')) }}" 
                           target="_blank"
                           class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-medium transition-colors">
                            Preview
                        </a>
                    </div>
                </div>

                <!-- Embed Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Embed Code</label>
                    <div class="mb-3">
                        <label class="text-xs text-gray-600">Width:</label>
                        <div class="flex gap-2 mt-1">
                            <label class="inline-flex items-center">
                                <input type="radio" x-model="embedWidth" value="100%" class="mr-1" checked>
                                <span class="text-sm">100%</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" x-model="embedWidth" value="600px" class="mr-1">
                                <span class="text-sm">600px</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" x-model="embedWidth" value="800px" class="mr-1">
                                <span class="text-sm">800px</span>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-xs text-gray-600">Height:</label>
                        <div class="flex gap-2 mt-1">
                            <label class="inline-flex items-center">
                                <input type="radio" x-model="embedHeight" value="600px" class="mr-1" checked>
                                <span class="text-sm">600px</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" x-model="embedHeight" value="800px" class="mr-1">
                                <span class="text-sm">800px</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" x-model="embedHeight" value="100vh" class="mr-1">
                                <span class="text-sm">Full Height</span>
                            </label>
                        </div>
                    </div>
                    <textarea readonly 
                              x-model="embedCode"
                              rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm font-mono"
                              onclick="this.select()"></textarea>
                    <button @click="copyToClipboard(embedCode)"
                            class="mt-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium transition-colors">
                        <span x-text="copiedEmbed ? 'Copied!' : 'Copy Embed Code'"></span>
                    </button>
                </div>

                <!-- QR Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">QR Code</label>
                    <div class="inline-block p-3 bg-white border-2 border-gray-300 rounded-lg">
                        <img :src="qrCodeUrl" alt="QR Code" class="w-48 h-48">
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Scan to access the flow on mobile devices</p>
                </div>
            </div>

            <!-- Message when not public -->
            <div x-show="!isPublic" x-transition class="text-sm text-gray-500 mt-2">
                Enable public access to get a shareable link and embed code
            </div>
        </div>

        <!-- Flow Info -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Flow Information</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Questions:</span>
                    <span class="font-medium">{{ count($flow->cards) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Runs:</span>
                    <span class="font-medium">{{ $flow->runs()->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Anonymous Runs:</span>
                    <span class="font-medium">{{ $flow->runs()->anonymous()->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Created:</span>
                    <span class="font-medium">{{ $flow->created_at->format('M d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function flowSettings() {
            return {
                isPublic: {{ $flow->is_public ? 'true' : 'false' }},
                copied: false,
                copiedEmbed: false,
                embedWidth: '100%',
                embedHeight: '600px',
                
                get embedCode() {
                    const url = '{{ $flow->publicUrl() ?? url('/p/' . ($flow->public_slug ?: 'not-set')) }}';
                    return `<iframe src="${url}" width="${this.embedWidth}" height="${this.embedHeight}" frameborder="0" style="border: none;"></iframe>`;
                },
                
                get qrCodeUrl() {
                    const url = encodeURIComponent('{{ $flow->publicUrl() ?? url('/p/' . ($flow->public_slug ?: 'not-set')) }}');
                    return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${url}`;
                },
                
                async togglePublic() {
                    console.log('Toggling public access to:', this.isPublic);
                    
                    try {
                        const response = await fetch('/flow/{{ $flow->id }}/toggle-public', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ is_public: this.isPublic })
                        });
                        
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);
                        
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            const text = await response.text();
                            console.error('Expected JSON but got:', text.substring(0, 200));
                            alert('Server returned HTML instead of JSON. Check console for details.');
                            this.isPublic = !this.isPublic;
                            return;
                        }
                        
                        const data = await response.json();
                        console.log('Response data:', data);
                        
                        if (response.ok) {
                            if (this.isPublic && data.public_url) {
                                // Reload to get the new slug
                                window.location.reload();
                            }
                        } else {
                            console.error('Server error:', data);
                            alert('Failed to update public access: ' + (data.message || 'Unknown error'));
                            this.isPublic = !this.isPublic;
                        }
                    } catch (error) {
                        console.error('Request error:', error);
                        alert('Error updating settings: ' + error.message);
                        this.isPublic = !this.isPublic;
                    }
                },
                
                copyToClipboard(text) {
                    navigator.clipboard.writeText(text).then(() => {
                        if (text.includes('<iframe')) {
                            this.copiedEmbed = true;
                            setTimeout(() => this.copiedEmbed = false, 2000);
                        } else {
                            this.copied = true;
                            setTimeout(() => this.copied = false, 2000);
                        }
                    });
                }
            }
        }
    </script>
@endsection

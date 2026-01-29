@extends('layouts.main')

@section('content')
    <div class="flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="w-full space-y-8 p-6">
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif
            
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                @if (! $flows->isEmpty())
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Name</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Description</th>
                                <th class="px-4 py-2 text-center text-sm font-medium text-gray-600">Questions</th>
                                <th class="px-4 py-2 text-center text-sm font-medium text-gray-600">Runs</th>
                                <th class="px-4 py-2 text-center text-sm font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($flows as $svaip)
                            <tr class="hover:bg-gray-100 cursor-pointer">
                                <td class="px-4 py-2 text-gray-900">{{ $svaip->name }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $svaip->shortDescription() ?? '—' }}</td>
                                <td class="px-4 py-2 text-center text-gray-700">{{ count($svaip->cards) }}</td>
                                <td class="px-4 py-2 text-center text-gray-700">{{ $svaip->runs()->count() }}</td>
                                <td class="px-4 py-2 text-center space-x-2">
                                    <a href="{{ route('flow.run', $svaip) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                        ▶ Run
                                    </a>
                                    <a href="{{ route('flow.edit', $svaip->id) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                        ✏ Edit
                                    </a>
                                    <a href="{{ route('flow.responses', $svaip) }}" class="inline-flex items-center px-3 py-1.5 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700">
                                        📋 Responses
                                    </a>
                                    <a href="{{ route('flow.analytics', $svaip->id) }}" class="inline-flex items-center px-3 py-1.5 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700">
                                        📊 Analytics
                                    </a>
                                    <a href="{{ route('flow.settings', $svaip->id) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700">
                                        ⚙ Settings
                                    </a>
                                    <button onclick="navigator.clipboard.writeText('{{ route('flow.run', $svaip) }}')" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                        ⧉ Copy url
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            @if ($flows->isEmpty())
                <div class="text-center text-gray-600">
                    <i class="fa-regular fa-3x text-indigo-500 fa-hand-spock"></i>
                    <p class="text-lg my-4">Hi!</p>
                    <p class="text-lg my-4">No Svaips here - ready to create one?</p>
                    <i class="fa-regular fa-3x fa-hand-point-down text-indigo-500"></i>
                </div>
            @endif

        </div>

        <hr />

        <div class="max-w-md w-full mt-4">
            <a href="{{ route('flow.create') }}">
            <div class="cursor-pointer p-2 w-full text-center border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create a Svaip
                </div>
            </a>
        </div>
    </div>

@endsection

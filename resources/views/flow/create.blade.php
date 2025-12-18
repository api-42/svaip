@extends('layouts.main')

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('data', () => ({
                svaip: {
                    name: '',
                    cards: [],
                    description: '',
                },

                init() {
                    this.addCard();
                },

                addCard() {
                    this.svaip.cards.push({
                        question: '',
                        description: '',
                        skipable: false,
                        options: ['Yes', 'No'],
                        branches: {0: null, 1: null},
                    });
                },

                removeCard(index) {
                    if (this.svaip.cards.length == 1) return;

                    this.svaip.cards.splice(index, 1);
                },

                saveSvaip() {
                    fetch('/api/flow', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(this.svaip),
                    }).then(response => response.json())
                      .then(data => {
                        console.log('Success:', data);
                      });
                }
            }));
        });
    </script>
@endpush
@section('content')
    <div class="flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <form x-data="data()" class="max-w-md w-full space-y-8">
            @csrf
            <div>
                <h2 class="text-center text-2xl text-gray-900">
                    Create a new svaip
                </h2>
            </div>
            <div>
                <div class="mb-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input x-model="svaip.name" id="name" name="name" type="text" required
                        class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                        placeholder="Descriptive name for your svaip">
                </div>
                <div class="mb-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description (optional)</label>
                    <input x-model="svaip.description" id="description" name="description" type="text" required
                        class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                        placeholder="Description of your svaip">
                </div>
                <p class="pt-2 mb-2 text-sm text-gray-700">
                    Give your svaip a descriptive name so you can easily identify it later and makes sense for others.
                </p>
                <p class="pt-2 mb-2 text-sm text-gray-700">
                    Formulate the questions as precise and clear as possible, while the description can provide additional context or information to help the user make an informed decision.
                </p>
                <p class="pt-2 mb-2 text-sm text-gray-700">
                    The questions should be possible to answer with a simple left or right swipe (e.g., Yes/No, Agree/Disagree, Like/Dislike).
                </p>
                <p class="pt-2 mb-2 text-sm text-gray-700">
                    Consider whether each question should be skipable or mandatory based on its importance in the overall information extraction for the svaip. If a question is critical for the svaip's outcome, it should be mandatory.
                    If you mark a question as skipable, users will have the option to skip it during the svaip.
                </p>
            </div>
            <template x-for="(card, index) in svaip.cards">
                <div class="border border-gray-300 rounded-md p-4 space-y-4 shadow-md" :key="index">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-medium text-gray-900"> # <span x-text="index + 1"></span> </h3>
                        <i class="text-orange-500 cursor-pointer fa-solid fa-lg fa-circle-xmark hover:text-red-500" x-on:click="removeCard(index)"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"> Question </label>
                        <input x-model="card.question" type="text" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="A precise question to ask the user">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"> Description (optional) </label>
                        <textarea x-model="card.description" rows="4"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Description intended to give the question more context"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700"> Left swipe </label>
                            <input x-model="card.options[0]" type="text" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter option 1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700"> Right swipe </label>
                            <input x-model="card.options[1]" type="text" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter option 2">
                        </div>
                        <div>
                            <label class="inline-flex items-center mt-3">
                                <input type="checkbox" x-model="card.skipable" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700"> Skipable </span>
                            </label>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700"> If left swipe, go to: </label>
                            <select x-model="card.branches[0]" 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option :value="null">Next card in sequence</option>
                                <template x-for="(c, i) in svaip.cards" :key="i">
                                    <option :value="i+1" x-show="i !== index" x-text="'Card ' + (i+1) + ': ' + (c.question || 'Untitled')"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700"> If right swipe, go to: </label>
                            <select x-model="card.branches[1]" 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option :value="null">Next card in sequence</option>
                                <template x-for="(c, i) in svaip.cards" :key="i">
                                    <option :value="i+1" x-show="i !== index" x-text="'Card ' + (i+1) + ': ' + (c.question || 'Untitled')"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </template>
            <div>
                <button type="button" x-on:click="addCard()"
                    class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Add card
                </button>
            </div>

            <hr />

            <div>
                <button type="button" x-on:click="saveSvaip()"
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save svaip
                </button>
            </div>
        </form>
    </div>
@endsection

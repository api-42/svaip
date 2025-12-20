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
                viewMode: 'visual', // 'visual' or 'form'
                selectedCard: null,
                canvas: null,
                ctx: null,
                connecting: false,
                connectFrom: null,
                connectSide: null,

                init() {
                    this.addCard();
                    this.$nextTick(() => {
                        this.canvas = this.$refs.canvas;
                        if (this.canvas) {
                            this.ctx = this.canvas.getContext('2d');
                            this.resizeCanvas();
                            window.addEventListener('resize', () => this.resizeCanvas());
                        }
                    });
                },

                resizeCanvas() {
                    if (!this.canvas) return;
                    const container = this.canvas.parentElement;
                    this.canvas.width = Math.max(container.scrollWidth, 1200);
                    this.canvas.height = Math.max(container.scrollHeight, 600);
                    this.drawConnections();
                },

                addCard() {
                    const newCard = {
                        question: '',
                        description: '',
                        skipable: false,
                        options: ['Yes', 'No'],
                        branches: {0: null, 1: null},
                        x: 100 + (this.svaip.cards.length * 50),
                        y: 100 + (this.svaip.cards.length * 30),
                    };
                    this.svaip.cards.push(newCard);
                    this.selectedCard = this.svaip.cards.length - 1;
                },

                removeCard(index) {
                    if (this.svaip.cards.length == 1) return;
                    
                    // Remove branches pointing to this card
                    const cardIndex = index + 1;
                    this.svaip.cards.forEach(card => {
                        if (card.branches[0] === cardIndex) card.branches[0] = null;
                        if (card.branches[1] === cardIndex) card.branches[1] = null;
                    });

                    this.svaip.cards.splice(index, 1);
                    if (this.selectedCard === index) this.selectedCard = null;
                    if (this.selectedCard > index) this.selectedCard--;
                    this.$nextTick(() => this.drawConnections());
                },

                startConnection(index, side) {
                    this.connecting = true;
                    this.connectFrom = index;
                    this.connectSide = side;
                },

                finishConnection(index) {
                    if (this.connecting && this.connectFrom !== null && this.connectFrom !== index) {
                        this.svaip.cards[this.connectFrom].branches[this.connectSide] = index + 1;
                        this.$nextTick(() => this.drawConnections());
                    }
                    this.connecting = false;
                    this.connectFrom = null;
                    this.connectSide = null;
                },

                removeConnection(cardIndex, side) {
                    this.svaip.cards[cardIndex].branches[side] = null;
                    this.$nextTick(() => this.drawConnections());
                },

                drawConnections() {
                    if (!this.ctx || !this.canvas) return;

                    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

                    this.svaip.cards.forEach((card, fromIndex) => {
                        [0, 1].forEach(side => {
                            const targetIndex = card.branches[side];
                            if (targetIndex !== null) {
                                const toIndex = targetIndex - 1;
                                if (toIndex >= 0 && toIndex < this.svaip.cards.length) {
                                    this.drawArrow(fromIndex, toIndex, side);
                                }
                            }
                        });
                    });
                },

                drawArrow(fromIndex, toIndex, side) {
                    const fromCard = this.svaip.cards[fromIndex];
                    const toCard = this.svaip.cards[toIndex];
                    
                    const startX = fromCard.x + (side === 0 ? 30 : 170);
                    const startY = fromCard.y + 150;
                    const endX = toCard.x + 100;
                    const endY = toCard.y;

                    this.ctx.strokeStyle = side === 0 ? '#ef4444' : '#10b981';
                    this.ctx.lineWidth = 3;
                    this.ctx.setLineDash([5, 5]);
                    
                    this.ctx.beginPath();
                    this.ctx.moveTo(startX, startY);
                    
                    // Calculate control points for smooth curve
                    const controlY1 = startY + Math.abs(endY - startY) * 0.5;
                    const controlY2 = endY - Math.abs(endY - startY) * 0.5;
                    
                    this.ctx.bezierCurveTo(
                        startX, controlY1,
                        endX, controlY2,
                        endX, endY
                    );
                    this.ctx.stroke();
                    
                    // Draw arrowhead
                    this.ctx.setLineDash([]);
                    const headSize = 10;
                    this.ctx.beginPath();
                    this.ctx.moveTo(endX, endY);
                    this.ctx.lineTo(endX - headSize, endY - headSize);
                    this.ctx.lineTo(endX + headSize, endY - headSize);
                    this.ctx.closePath();
                    this.ctx.fillStyle = side === 0 ? '#ef4444' : '#10b981';
                    this.ctx.fill();
                },

                

                cancel() {
                    const answer = confirm('Are you sure you want to cancel? All unsaved data will be lost.');
                    if (!answer) return;
                    window.location.href = "{{ route('flow.index') }}";
                },
            }));
        });
    </script>
@endpush

@section('content')
    <div class="px-4 sm:px-6 lg:px-8">
        <form x-data="data()" action="{{ route('flow.store') }}" method="POST">
            @csrf
            
            <!-- View Mode Toggle -->
            <div class="flex gap-2 mb-4">
                <button type="button" @click="viewMode = 'visual'" 
                    :class="viewMode === 'visual' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'"
                    class="px-4 py-2 rounded-md border border-gray-300 font-medium">
                    <i class="fa-solid fa-diagram-project mr-2"></i>Visual Flow
                </button>
                <button type="button" @click="viewMode = 'form'" 
                    :class="viewMode === 'form' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'"
                    class="px-4 py-2 rounded-md border border-gray-300 font-medium">
                    <i class="fa-solid fa-list mr-2"></i>Form View
                </button>
            </div>

            <!-- Basic Info -->
            <div class="bg-white p-6 rounded-lg shadow-lg mb-4">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input x-model="svaip.name" id="name" name="name" autocomplete="off" type="text" required
                        class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Descriptive name for your svaip">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                    <input x-model="svaip.description" id="description" name="description" autocomplete="off" type="text"
                        class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Description of your svaip">
                </div>
            </div>

            <!-- Visual Flow View -->
            <div x-show="viewMode === 'visual'" class="bg-white rounded-lg shadow-lg p-4">
                <div class="mb-4 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Flow Designer</h3>
                    <button type="button" @click="addCard()"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">
                        <i class="fa-solid fa-plus mr-2"></i>Add Card
                    </button>
                </div>
                
                <div class="relative bg-gray-50 rounded-lg border-2 border-gray-200" style="height: 600px; overflow: auto;" x-ref="scrollContainer">
                    <canvas x-ref="canvas" class="absolute top-0 left-0 pointer-events-none" style="z-index: 1;" width="1200" height="600"></canvas>
                    
                    <div style="position: relative; z-index: 2; min-height: 600px; min-width: 1200px;">
                        <template x-for="(card, index) in svaip.cards" :key="index">
                            <div class="absolute bg-white rounded-[0.6rem] shadow-md border-2 cursor-move overflow-hidden"
                                :style="`left: ${card.x}px; top: ${card.y}px; width: 200px;`"
                                :class="selectedCard === index ? 'border-indigo-500' : 'border-gray-300'"
                                @mousedown.prevent="selectedCard = index"
                                x-init="
                                    let isDragging = false;
                                    let offsetX, offsetY;
                                    $el.addEventListener('mousedown', (e) => {
                                        if (e.target.tagName === 'BUTTON' || e.target.tagName === 'I') return;
                                        isDragging = true;
                                        const rect = $refs.scrollContainer.getBoundingClientRect();
                                        offsetX = e.clientX - rect.left + $refs.scrollContainer.scrollLeft - card.x;
                                        offsetY = e.clientY - rect.top + $refs.scrollContainer.scrollTop - card.y;
                                    });
                                    document.addEventListener('mousemove', (e) => {
                                        if (isDragging) {
                                            const rect = $refs.scrollContainer.getBoundingClientRect();
                                            card.x = e.clientX - rect.left + $refs.scrollContainer.scrollLeft - offsetX;
                                            card.y = e.clientY - rect.top + $refs.scrollContainer.scrollTop - offsetY;
                                            drawConnections();
                                        }
                                    });
                                    document.addEventListener('mouseup', () => {
                                        isDragging = false;
                                    });
                                ">
                                <div class="p-3 bg-gradient-to-r from-indigo-500 to-purple-500 text-white flex justify-between items-center">
                                    <span class="font-semibold text-sm">Card #<span x-text="index + 1"></span></span>
                                    <button type="button" @click.stop="removeCard(index)" @mousedown.stop class="text-white hover:text-red-200">
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                </div>
                                <div class="p-3">
                                    <div class="text-xs font-medium text-gray-700 mb-1">Question:</div>
                                    <div class="text-sm text-gray-900 mb-2 line-clamp-2" x-text="card.question || ''"></div>
                                    
                                    <div class="flex gap-2 mt-3">
                                        <div class="flex-1">
                                            <div class="text-xs text-gray-500 mb-1" x-text="card.options[0]"></div>
                                            <button type="button" 
                                                @click.stop="card.branches[0] !== null ? removeConnection(index, 0) : startConnection(index, 0)"
                                                @mousedown.stop
                                                :class="card.branches[0] !== null ? 'bg-red-500 hover:bg-red-600' : connecting && connectFrom === index && connectSide === 0 ? 'bg-red-400' : 'bg-red-100 hover:bg-red-200'"
                                                class="w-full text-xs py-1 px-2 rounded text-white">
                                                <i :class="card.branches[0] !== null ? 'fa-link-slash' : 'fa-link'" class="fa-solid"></i>
                                            </button>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-xs text-gray-500 mb-1" x-text="card.options[1]"></div>
                                            <button type="button"
                                                @click.stop="card.branches[1] !== null ? removeConnection(index, 1) : startConnection(index, 1)"
                                                @mousedown.stop
                                                :class="card.branches[1] !== null ? 'bg-green-500 hover:bg-green-600' : connecting && connectFrom === index && connectSide === 1 ? 'bg-green-400' : 'bg-green-100 hover:bg-green-200'"
                                                class="w-full text-xs py-1 px-2 rounded text-white">
                                                <i :class="card.branches[1] !== null ? 'fa-link-slash' : 'fa-link'" class="fa-solid"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div x-show="connecting && connectFrom !== index" 
                                    @click.stop="finishConnection(index)"
                                    @mousedown.stop
                                    class="absolute inset-0 bg-indigo-500 bg-opacity-20 rounded-lg flex items-center justify-center cursor-pointer border-2 border-indigo-500 border-dashed">
                                    <span class="text-indigo-700 font-semibold">Click to connect</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Card Editor Panel -->
                <div x-show="selectedCard !== null" class="mt-4 bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <template x-if="selectedCard !== null && svaip.cards[selectedCard]">
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 mb-3">
                                Edit Card #<span x-text="selectedCard + 1"></span>
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Question</label>
                                    <input x-model="svaip.cards[selectedCard].question" type="text" 
                                        :name="`cards[${selectedCard}][question]`" required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="A precise question to ask the user">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                                    <textarea x-model="svaip.cards[selectedCard].description" rows="2" 
                                        :name="`cards[${selectedCard}][description]`"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Additional context"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Left swipe text</label>
                                    <input x-model="svaip.cards[selectedCard].options[0]" 
                                        :name="`cards[${selectedCard}][options][0]`" type="text" required
                                        @input="$nextTick(() => drawConnections())"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Right swipe text</label>
                                    <input x-model="svaip.cards[selectedCard].options[1]" 
                                        :name="`cards[${selectedCard}][options][1]`" type="text" required
                                        @input="$nextTick(() => drawConnections())"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" x-model="svaip.cards[selectedCard].skipable" 
                                            :name="`cards[${selectedCard}][skipable]`"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <span class="ml-2 text-sm text-gray-700">Skipable</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Form View (Original) -->
            <div x-show="viewMode === 'form'">
                <div class="space-y-2 mb-8 bg-white p-6 rounded-lg shadow-lg">
                    <div class="text-center text-yellow-500 mb-2">
                        <i class="fa-regular fa-lightbulb fa-3x"></i>
                    </div>
                    <p class="text-center text-lg text-gray-900 font-medium">
                        Tips to get the most out of each svaip
                    </p>
                    <p class="pt-2 text-sm text-gray-700">
                        1. Give your svaip a descriptive name soo the users can easily identify its purpose.
                    </p>
                    <p class="pt-2 text-sm text-gray-700">
                        2. Formulate each question as precise and clear as possible, while the description can provide additional context or information to help the user make an informed decision.
                    </p>
                    <p class="pt-2 text-sm text-gray-700">
                        3. The questions should be binary in nature, meaning they can be answered with two distinct options (e.g., Yes/No, Accept/Reject, Like/Dislike, Beef/Fish, Breakfast/No breakfast, etc.).
                    </p>
                </div>
                <template x-for="(card, index) in svaip.cards" :key="index">
                    <div class="border border-gray-300 rounded-md p-4 space-y-4 shadow-md mb-2 bg-white">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-lg font-medium text-gray-900"># <span x-text="index + 1"></span></h3>
                            <i class="text-orange-500 cursor-pointer fa-solid fa-lg fa-circle-xmark hover:text-red-500" @click="removeCard(index)"></i>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Question</label>
                            <input x-model="card.question" type="text" :name="`cards[${index}][question]`" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="A precise question to ask the user">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description (optional)</label>
                            <textarea x-model="card.description" rows="4" :name="`cards[${index}][description]`"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Description intended to give the question more context"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Left swipe</label>
                                <input x-model="card.options[0]" :name="`cards[${index}][options][0]`" type="text" required
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter option 1">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Right swipe</label>
                                <input x-model="card.options[1]" :name="`cards[${index}][options][1]`" type="text" required
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter option 2">
                            </div>
                            <div>
                                <label class="inline-flex items-center mt-3">
                                    <input type="checkbox" x-model="card.skipable" :name="`cards[${index}][skipable]`"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Skipable</span>
                                </label>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">If left swipe, go to:</label>
                                <select x-model="card.branches[0]" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option :value="null">Next card in sequence</option>
                                    <template x-for="(c, i) in svaip.cards" :key="i">
                                        <option :value="i+1" x-show="i !== index" x-text="'Card ' + (i+1) + ': ' + (c.question || '')"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">If right swipe, go to:</label>
                                <select x-model="card.branches[1]" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option :value="null">Next card in sequence</option>
                                    <template x-for="(c, i) in svaip.cards" :key="i">
                                        <option :value="i+1" x-show="i !== index" x-text="'Card ' + (i+1) + ': ' + (c.question || '')"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>
                <button type="button" @click="addCard()"
                    class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mb-4">
                    Add a card
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 space-y-4">

            <!-- Action Buttons -->
            <div class="mt-6 space-y-4">
                <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save Svaip
                </button>

                <button type="button" x-on:click="cancel()"
                    class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Discard Svaip
                </button>
            </div>
        </form>
    </div>
@endsection

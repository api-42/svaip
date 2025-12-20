@extends('layouts.main', ['clean' => true])

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('state', () => ({
                offsetX: 0,
                rotation: 0,
                startX: null,
                leaning: -1,
                sensitivity: 20,
                currentCard: null,
                flow: @json($flow),

                init() {
                    console.log('Flow data:', this.flow);
                    this.currentCard = this.flow.cards[0];
                },

                startDrag(e) {
                    this.startX = e.clientX;
                    e.target.setPointerCapture(e.pointerId);
                },

                drag(e) {
                    if (this.startX === null) return;
                        const moveX = e.pageX - this.startX;

                        if (moveX > 0) {
                            this.leaning = 1;
                        } else if (moveX < 0) {
                            this.leaning = 0;
                        } else {
                            this.leaning = -1;
                        }

                        const limit = window.innerWidth < 640 ? 25 : 40; // smaller limit on small screens
                        console.log('Moving:', limit);
                        this.offsetX = Math.max(Math.min(moveX, limit), -limit);
                        this.rotation = this.offsetX / this.sensitivity;
                 },

                stop() {
                    fetch(`/api/run/${this.flow.id}/stop`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                    })
                },

                endDrag(e) {
                    e?.target?.releasePointerCapture?.(e.pointerId);

                    if (this.leaning !== -1) {
                        const currentCardId = this.currentCard.id;
                        const currentIndex = this.flow.cards.findIndex(card => card.id === currentCardId);

                        if (currentIndex < this.flow.cards.length - 1) {
                            this.currentCard = this.flow.cards[currentIndex + 1];
                        } else {
                            this.currentCard = 'end';
                        }

                        fetch(`/api/run/${this.flow.id}/answer`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                answer: this.leaning,
                                card_id: currentCardId,
                            }),
                        })
                        .then(r => r.json())
                        .then(data => {
                            console.log('Answer saved', data);
                            if (this.currentCard === 'end') {
                                console.log('Flow ended');
                                this.stop();
                            }
                        })
                        .catch(err => {
                            console.error('Failed to save answer', err);
                        });
                    }

                    this.offsetX = 0;
                    this.rotation = 0;
                    this.startX = null;
                    this.leaning = -1;
                }

            }));
        });
    </script>
@endpush
@section('content')
    <body x-data="state()" class="bg-gray-100 p-4 touch-none bg-gradient-to-b from-slate-100 via-gray-100 to-slate-100 bg-no-repeat bg-fixed">
        <template x-if="(flow && flow.cards.length > 0) && currentCard != 'end'">
            <div class="flex justify-center items-center h-[60vh]">
                <div class="absolute inset-0 flex justify-center items-center gap-10 text-gray-300 font-semibold text-4xl sm:text-5xl px-10 sm:px-16 pointer-events-none select-none">
                    <span class="mr-5" x-bind:class="{'text-gray-400': leaning !== 0, 'text-emerald-600': leaning === 0}" x-text="currentCard.options[0]"></span>
                    <span class="border-l border-gray-300 h-[60%]"></span>
                    <span class="ml-5" x-bind:class="{'text-gray-400': leaning !== 1, 'text-emerald-600': leaning === 1}" x-text="currentCard.options[1]"></span>
                </div>
                    <div @pointerdown="startDrag($event)"
                        @pointermove="drag($event)"
                        @pointerup="endDrag($event)"
                        @pointercancel="endDrag($event)" x-bind:style="`transform: rotate(${rotation}deg) translateX(${offsetX}px); transition: transform 0.15s ease-out;`"
                        class="select-none max-w-md w-full bg-white shadow-md rounded-2xl p-6 flex flex-col justify-between"
                        x-bind:class="startX !== null ? 'cursor-grabbing' : 'cursor-grab'"
                        >
                        <div>
                            <h2 class="text-base text-center sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4" x-text="currentCard.question"> </h2>
                            <p class="text-gray-500 text-sm sm:text-base mb-4" x-text="currentCard.description"> </p>
                        </div>
                    </div>
            </div>
        </template>
        <template x-if="currentCard === 'end'">
            <div class="flex flex-col justify-center items-center h-[60vh]">
                <h2 class="text-2xl sm:text-3xl font-semibold text-gray-800 mb-4">You have reached the Svaips end.</h2>
            </div>
        </template>
    </body>
@endsection

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Geist', sans-serif;
            }
        </style>
    </head>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('data', () => ({
                flow: null,
                flowId: {{ $id }},
                runId: {{ $runId }},

                offsetX: 0,
                rotation: 0,
                startX: null,
                leaning: -1,
                currentCard: null,
                sensitivity: 20,

                init() {
                    fetch('/api/flow/' + this.flowId + '/run/' + this.runId + '/start')
                        .then(response => response.json())
                        .then(data => {
                            this.flow = data.data;
                            this.currentCard = this.flow.cards[0];
                        });
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

                endDrag(e) {
                    e?.target?.releasePointerCapture?.(e.pointerId);

                    console.log('Dropped on:', this.leaning);
                    if (this.leaning !== -1) {
                        fetch('/api/flow/' + this.flowId + '/run/' + this.runId + '/answer', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                card_id: this.currentCard.id,
                                answer: this.leaning,
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Answer recorded:', data);
                            // Use branching logic from server response
                            if (data.next_card) {
                                this.currentCard = data.next_card;
                            } else {
                                // Flow finished
                                this.currentCard = null;
                                fetch('/api/flow/' + this.flowId + '/run/' + this.runId + '/stop', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json'},
                                });
                            }
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
    <body x-data="data()" class="bg-gray-100 p-4 touch-none bg-gradient-to-b from-slate-100 via-gray-100 to-slate-100 bg-no-repeat bg-fixed">
        <template x-if="flow && flow.cards.length > 0">
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
                            <h2 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4" x-text="currentCard.question"> </h2>
                            <p class="text-gray-500 text-sm sm:text-base mb-4" x-text="currentCard.description"> </p>
                        </div>
                    </div>
            </div>
        </template>
    </body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $flow->name }} - SVAIP</title>
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
        Alpine.data('flowState', () => ({
            offsetX: 0,
            rotation: 0,
            startX: null,
            leaning: -1,
            sensitivity: 20,
            currentCard: @json($card),
            flowSlug: '{{ $flow->public_slug }}',
            runId: '{{ $run->id }}',
            cardPosition: {{ $cardPosition }},
            totalCards: {{ $totalCards }},

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

                const limit = window.innerWidth < 640 ? 25 : 40;
                this.offsetX = Math.max(Math.min(moveX, limit), -limit);
                this.rotation = this.offsetX / this.sensitivity;
            },

            endDrag(e) {
                e?.target?.releasePointerCapture?.(e.pointerId);

                if (this.leaning !== -1) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/p/${this.flowSlug}/run/${this.runId}/answer`;
                    
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    
                    const cardInput = document.createElement('input');
                    cardInput.type = 'hidden';
                    cardInput.name = 'card_id';
                    cardInput.value = this.currentCard.id;
                    
                    const answerInput = document.createElement('input');
                    answerInput.type = 'hidden';
                    answerInput.name = 'answer';
                    answerInput.value = this.leaning;
                    
                    form.appendChild(csrfInput);
                    form.appendChild(cardInput);
                    form.appendChild(answerInput);
                    document.body.appendChild(form);
                    form.submit();
                }

                this.offsetX = 0;
                this.rotation = 0;
                this.startX = null;
                this.leaning = -1;
            }
        }));
    });
</script>
<body x-data="flowState()" class="bg-gray-100 p-4 touch-none bg-gradient-to-b from-slate-100 via-gray-100 to-slate-100 bg-no-repeat bg-fixed">
    <div class="flex justify-center items-center min-h-screen py-8">
        <!-- Swipeable Card with Unified Design -->
        <div @pointerdown="startDrag($event)"
            @pointermove="drag($event)"
            @pointerup="endDrag($event)"
            @pointercancel="endDrag($event)" 
            x-bind:style="`transform: rotate(${rotation}deg) translateX(${offsetX}px); transition: transform 0.15s ease-out;`"
            class="select-none max-w-sm sm:max-w-lg w-full bg-white shadow-lg rounded-2xl overflow-hidden flex flex-col"
            x-bind:class="startX !== null ? 'cursor-grabbing' : 'cursor-grab'">
            
            <!-- Card Header - Minimal -->
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 flex justify-end">
                <span class="text-gray-500 text-xs font-medium">
                    <span x-text="cardPosition"></span> / <span x-text="totalCards"></span>
                </span>
            </div>
            
            <!-- Card Content -->
            <div class="p-6 sm:p-8 flex-1 flex flex-col justify-center">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-3 text-center" x-text="currentCard.question"></h2>
                <p class="text-gray-600 text-sm sm:text-base text-center" x-text="currentCard.description"></p>
            </div>

            <!-- Card Footer - Swipe Options -->
            <div class="px-4 py-3 bg-gradient-to-r from-gray-50 to-gray-100 border-t border-gray-100 flex justify-between items-center text-sm sm:text-base font-medium">
                <span class="transition-all duration-200 flex items-center gap-1"
                    x-bind:class="leaning === 0 ? 'text-indigo-600 scale-110' : 'text-gray-400'">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span x-text="currentCard.options[0]"></span>
                </span>
                <span class="text-gray-300">|</span>
                <span class="transition-all duration-200 flex items-center gap-1"
                    x-bind:class="leaning === 1 ? 'text-indigo-600 scale-110' : 'text-gray-400'">
                    <span x-text="currentCard.options[1]"></span>
                    <i class="fa-solid fa-arrow-right"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="fixed bottom-4 left-0 right-0 text-center text-sm text-gray-400">
        <p>Powered by SVAIP</p>
    </div>
</body>
</html>

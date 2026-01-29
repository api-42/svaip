{{-- Preview modal component - embedded in create.blade.php --}}
{{-- This provides a swipe-based card deck preview for testing flows --}}

<!-- Preview Modal Backdrop -->
<div x-show="showPreview" x-cloak
    class="fixed inset-0 z-50 overflow-hidden"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">
    
    <div class="absolute inset-0 bg-gradient-to-b from-slate-100 via-gray-100 to-slate-100">
        <!-- Close button -->
        <button type="button" @click="closePreview()" 
            class="absolute top-4 right-4 z-10 p-2 rounded-full bg-white shadow-md hover:bg-gray-100">
            <i class="fa-solid fa-times text-gray-600 text-xl"></i>
        </button>

        <!-- Restart button -->
        <button type="button" @click="restartPreview()" 
            class="absolute top-4 left-4 z-10 p-2 rounded-full bg-white shadow-md hover:bg-gray-100">
            <i class="fa-solid fa-redo text-gray-600"></i>
        </button>

        <!-- Preview Header -->
        <div class="absolute top-4 left-1/2 transform -translate-x-1/2 z-10">
            <div class="bg-white px-4 py-2 rounded-full shadow-md text-sm font-medium text-gray-700">
                <i class="fa-solid fa-eye mr-2 text-indigo-500"></i>
                Preview Mode
                <span class="ml-2 text-gray-400" x-show="!previewComplete">
                    Card <span x-text="previewCardIndex + 1"></span>/<span x-text="svaip.cards.length"></span>
                </span>
            </div>
        </div>

        <!-- Card Deck Area - Question Cards -->
        <template x-if="!previewComplete && svaip.cards[previewCardIndex] && svaip.cards[previewCardIndex].type !== 'end'">
            <div class="flex justify-center items-center h-full touch-none p-4">
                <!-- Swipeable Card -->
                <div @pointerdown="previewStartDrag($event)"
                    @pointermove="previewDrag($event)"
                    @pointerup="previewEndDrag($event)"
                    @pointercancel="previewEndDrag($event)"
                    :style="`transform: rotate(${previewRotation}deg) translateX(${previewOffsetX}px) translateY(${previewOffsetY}px); transition: transform 0.15s ease-out;`"
                    class="select-none max-w-sm sm:max-w-lg w-full bg-white shadow-lg rounded-2xl overflow-hidden flex flex-col"
                    :class="previewStartX !== null ? 'cursor-grabbing' : 'cursor-grab'">
                    
                    <!-- Card Header - Minimal -->
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 flex justify-end">
                        <span class="text-gray-500 text-xs font-medium">
                            <span x-text="previewCardIndex + 1"></span> / <span x-text="svaip.cards.length"></span>
                        </span>
                    </div>
                    
                    <!-- Card Content -->
                    <div class="p-6 sm:p-8 flex-1 flex flex-col justify-center">
                        <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-3 text-center" x-text="svaip.cards[previewCardIndex].question"></h2>
                        <p class="text-gray-600 text-sm sm:text-base text-center" 
                            x-show="svaip.cards[previewCardIndex].description" 
                            x-text="svaip.cards[previewCardIndex].description"></p>
                    </div>

                    <!-- Card Footer - Swipe Options -->
                    <div class="px-4 py-3 bg-gradient-to-r from-gray-50 to-gray-100 border-t border-gray-100 flex justify-between items-center text-sm sm:text-base font-medium">
                        <span class="transition-all duration-200 flex items-center gap-1"
                            :class="previewLeaning === 0 ? 'text-indigo-600 scale-110' : 'text-gray-400'">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span x-text="svaip.cards[previewCardIndex].options[0]"></span>
                        </span>
                        <span class="text-gray-300">|</span>
                        <span class="transition-all duration-200 flex items-center gap-1"
                            :class="previewLeaning === 1 ? 'text-indigo-600 scale-110' : 'text-gray-400'">
                            <span x-text="svaip.cards[previewCardIndex].options[1]"></span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <!-- End Card Display -->
        <template x-if="!previewComplete && svaip.cards[previewCardIndex] && svaip.cards[previewCardIndex].type === 'end'">
            <div class="flex flex-col justify-center items-center h-full px-4">
                <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden">
                    <!-- End Card Header -->
                    <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-6 py-4">
                        <span class="text-white text-sm font-medium opacity-75">
                            <i class="fa-solid fa-flag-checkered mr-1"></i>End Point
                        </span>
                    </div>
                    
                    <!-- End Card Content -->
                    <div class="p-6">
                        <div class="text-center mb-6">
                            <i class="fa-solid fa-circle-check text-5xl text-emerald-500 mb-4"></i>
                            <p class="text-lg text-gray-700" x-text="svaip.cards[previewCardIndex].message"></p>
                        </div>
                        
                        <!-- Form Fields Preview (if any) -->
                        <template x-if="svaip.cards[previewCardIndex].formFields && svaip.cards[previewCardIndex].formFields.length > 0">
                            <div class="space-y-4 border-t pt-4 mt-4">
                                <p class="text-sm text-gray-500 mb-2">This end point collects the following information:</p>
                                <template x-for="(field, idx) in svaip.cards[previewCardIndex].formFields" :key="idx">
                                    <div class="mb-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            <span x-text="field.label || 'Untitled field'"></span>
                                            <span x-show="field.required" class="text-red-500 ml-1">*</span>
                                        </label>
                                        <template x-if="field.type !== 'textarea'">
                                            <input :type="field.type" disabled
                                                class="block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-400"
                                                :placeholder="'(Preview: ' + field.type + ' input)'">
                                        </template>
                                        <template x-if="field.type === 'textarea'">
                                            <textarea disabled rows="2"
                                                class="block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-400"
                                                placeholder="(Preview: textarea input)"></textarea>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                        
                        <!-- Complete button -->
                        <button type="button" @click="previewComplete = true"
                            class="w-full mt-4 py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg">
                            <i class="fa-solid fa-check mr-2"></i>Complete
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Completion Screen -->
        <template x-if="previewComplete">
            <div class="flex flex-col justify-center items-center h-full px-4">
                <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-8 text-center">
                    <i class="fa-solid fa-check-circle text-6xl text-green-500 mb-4"></i>
                    <h2 class="text-2xl font-semibold text-gray-800 mb-2">Preview Complete!</h2>
                    <p class="text-gray-500 mb-6">You've gone through all <span x-text="previewAnswers.length"></span> cards.</p>
                    
                    <!-- Summary -->
                    <div class="max-h-48 overflow-y-auto mb-6 text-left">
                        <template x-for="(answer, idx) in previewAnswers" :key="idx">
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg text-sm mb-2">
                                <span class="text-gray-700 truncate flex-1 mr-2" x-text="answer.question"></span>
                                <span class="font-medium px-2 py-1 rounded text-xs whitespace-nowrap text-white"
                                        :class="answer.side === 0 ? 'bg-indigo-500' : 'bg-amber-500'"
                                    x-text="answer.answer"></span>
                            </div>
                        </template>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="restartPreview()"
                            class="flex-1 py-3 px-4 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 font-medium rounded-lg">
                            <i class="fa-solid fa-redo mr-2"></i>Restart
                        </button>
                        <button type="button" @click="closePreview()"
                            class="flex-1 py-3 px-4 bg-gray-800 hover:bg-gray-900 text-white font-medium rounded-lg">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

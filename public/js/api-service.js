/**
 * API Service for SVAIP Flow Management
 * Handles all API communication with consistent error handling
 */

class ApiError extends Error {
    constructor(message, status, errors = null) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }
}

class ApiService {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;
    }

    /**
     * Get CSRF token from meta tag
     */
    getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    /**
     * Make API request with error handling
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };

        const config = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new ApiError(
                    data.message || 'Request failed',
                    response.status,
                    data.errors || null
                );
            }

            return data;
        } catch (error) {
            if (error instanceof ApiError) {
                throw error;
            }
            
            // Network or parsing error
            throw new ApiError(
                error.message || 'Network error',
                0,
                null
            );
        }
    }

    /**
     * Flow API Methods
     */

    async getFlows(page = 1) {
        return this.request(`/flows?page=${page}`);
    }

    async getFlow(id) {
        return this.request(`/flows/${id}`);
    }

    async createFlow(flowData) {
        return this.request('/flows', {
            method: 'POST',
            body: JSON.stringify(flowData)
        });
    }

    async updateFlow(id, flowData) {
        return this.request(`/flows/${id}`, {
            method: 'PUT',
            body: JSON.stringify(flowData)
        });
    }

    async deleteFlow(id) {
        return this.request(`/flows/${id}`, {
            method: 'DELETE'
        });
    }

    async toggleFlowPublic(id) {
        return this.request(`/flows/${id}/toggle-public`, {
            method: 'POST'
        });
    }

    /**
     * Helper: Format flow data for API
     * Separates question cards from end cards
     */
    formatFlowData(name, description, cards, layout = {}) {
        const questionCards = [];
        const endCards = [];
        
        console.log('[FORMAT] Starting formatFlowData');
        console.log('[FORMAT] Input cards:', cards);
        
        // Build mapping: UI index → question-only index
        const uiToQuestionMap = new Map();
        let questionIdx = 0;
        cards.forEach((card, uiIdx) => {
            if (card.type !== 'end') {
                uiToQuestionMap.set(uiIdx, questionIdx);
                questionIdx++;
            }
        });
        console.log('[FORMAT] UI to Question mapping:', Array.from(uiToQuestionMap.entries()));

        cards.forEach((card, uiIndex) => {
            if (card.type === 'end') {
                console.log(`[FORMAT] Card ${uiIndex}: END CARD`);
                endCards.push({
                    type: 'end',
                    message: card.message,
                    position: layout[`end_${endCards.length}`] || { x: card.x || 0, y: card.y || 0 }
                });
            } else {
                console.log(`[FORMAT] Card ${uiIndex}: QUESTION CARD, branches:`, card.branches);
                
                // Convert branches from 1-based UI indices to 0-based question indices
                const convertedBranches = (card.branches || [null, null]).map(targetDisplayIndex => {
                    if (targetDisplayIndex === null) return null;
                    
                    const targetUiIndex = targetDisplayIndex - 1; // Convert 1-based to 0-based
                    const questionIndex = uiToQuestionMap.get(targetUiIndex);
                    
                    console.log(`[FORMAT]   Branch target ${targetDisplayIndex} (UI ${targetUiIndex}) → Question index ${questionIndex}`);
                    return questionIndex !== undefined ? questionIndex : null;
                });
                
                // Question card
                const cardData = {
                    question: card.question,
                    options: card.options || ['Yes', 'No'],
                    branches: convertedBranches,
                    position: layout[questionIdx] || { x: card.x || 0, y: card.y || 0 }
                };
                
                // Add optional fields
                if (card.description) cardData.description = card.description;
                if (card.scoring) cardData.scoring = card.scoring;
                if (card.skipable) cardData.skipable = card.skipable;

                console.log(`[FORMAT] Formatted card:`, cardData);
                questionCards.push(cardData);
            }
        });

        const result = {
            name,
            description,
            cards: questionCards,
            end_cards: endCards,
            layout
        };
        
        console.log('[FORMAT] Final result:', result);
        return result;
    }

    /**
     * Helper: Parse API response back to UI format
     * Merges question cards and end cards into single array
     */
    parseFlowData(flowData) {
        const cards = [];
        const layout = flowData.layout || {};

        // Add question cards
        (flowData.cards || []).forEach((card, index) => {
            cards.push({
                id: card.id,
                type: 'question',
                question: card.question,
                options: card.options || ['Yes', 'No'],
                branches: card.branches || [null, null],
                x: layout[index]?.x || 0,
                y: layout[index]?.y || 0
            });
        });

        // Add end cards
        (flowData.end_cards || []).forEach((endCard, index) => {
            cards.push({
                type: 'end',
                message: endCard.message,
                x: layout[`end_${index}`]?.x || 0,
                y: layout[`end_${index}`]?.y || 0
            });
        });

        return {
            id: flowData.id,
            name: flowData.name,
            description: flowData.description,
            cards,
            layout
        };
    }
}

// Create global instance
window.apiService = new ApiService();
window.ApiError = ApiError;

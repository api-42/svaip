/**
 * Test to demonstrate the branch saving bugs
 * 
 * Scenario: 3 cards in UI
 * - Card 0: Question "Do you like pizza?" with branches [null, 2] (right swipe goes to Card #2)
 * - Card 1: End card "Thanks!"
 * - Card 2: Question "Are you hungry?" with branches [null, null]
 * 
 * Expected behavior:
 * - Backend should receive 2 question cards (indices 0, 1)
 * - Card 0 branches should point to question card index 1 (which is Card 2 in UI)
 * - Layout for Card 0 should use key "0", Card 2 should use key "1"
 * 
 * Actual behavior (BUGS):
 * 1. Branch Bug: Card 0 branches [null, 2] is sent as-is to backend
 *    Backend looks for cardIdMapping[2] which doesn't exist (only has indices 0, 1)
 *    Result: Branch is saved as null
 * 
 * 2. Layout Bug in saveSvaip: Card 2 (question) gets layout key "2" instead of "1"
 *    because saveSvaip uses overall card index instead of question-only index
 * 
 * 3. Layout Bug in formatFlowData: Card 2 position lookup uses index 1 
 *    (question card index) but layout has it stored at key "2" (overall index)
 *    Result: Position is lost, uses fallback { x: card.x, y: card.y }
 */

// Simulating the UI data
const uiCards = [
    {
        type: 'question',
        question: 'Do you like pizza?',
        options: ['No', 'Yes'],
        branches: [null, 2], // UI index: right swipe to Card #2 (displayed as "Card #3" to user)
        x: 100,
        y: 100
    },
    {
        type: 'end',
        message: 'Thanks!',
        x: 300,
        y: 100
    },
    {
        type: 'question',
        question: 'Are you hungry?',
        options: ['No', 'Yes'],
        branches: [null, null],
        x: 500,
        y: 100
    }
];

// Simulating saveSvaip layout building (BUGGY)
console.log('\n=== BUG 1: saveSvaip builds layout with wrong keys ===');
const layoutBuggy = {};
let endCardCount = 0;
uiCards.forEach((card, index) => {
    if (card.type === 'end') {
        layoutBuggy[`end_${endCardCount}`] = { x: parseInt(card.x) || 0, y: parseInt(card.y) || 0 };
        endCardCount++;
    } else {
        // BUG: Using overall index, not question-only index
        layoutBuggy[index] = { x: parseInt(card.x) || 0, y: parseInt(card.y) || 0 };
    }
});
console.log('Layout built by saveSvaip:', JSON.stringify(layoutBuggy, null, 2));
console.log('PROBLEM: Question card at UI index 2 gets layout key "2" instead of "1"');

// Simulating formatFlowData (BUGGY)
console.log('\n=== BUG 2: formatFlowData uses wrong index for layout lookup ===');
const questionCards = [];
uiCards.forEach((card, index) => {
    if (card.type !== 'end') {
        const cardData = {
            question: card.question,
            options: card.options,
            branches: card.branches, // BUG: Not converting UI indices to question-only indices
            // BUG: Using question card index for layout lookup, but layout has overall index
            position: layoutBuggy[index] || { x: card.x || 0, y: card.y || 0 }
        };
        console.log(`Processing question card at UI index ${index}:`);
        console.log(`  Looking up layout[${index}]: ${layoutBuggy[index] ? 'FOUND' : 'NOT FOUND (using fallback)'}`);
        console.log(`  Position:`, cardData.position);
        questionCards.push(cardData);
    }
});

console.log('\n=== BUG 3: Branches sent to backend use UI indices, not question-only indices ===');
console.log('Question cards array sent to backend:');
questionCards.forEach((card, idx) => {
    console.log(`  [${idx}]:`, card.question, '- branches:', card.branches);
});

console.log('\nBackend processing (FlowService.php):');
const cardIdMapping = {
    0: 22, // First question card gets DB ID 22
    1: 23  // Second question card gets DB ID 23
};

questionCards.forEach((card, index) => {
    console.log(`\nCard ${index}: "${card.question}"`);
    if (card.branches) {
        card.branches.forEach((targetIndex, answer) => {
            if (targetIndex !== null) {
                console.log(`  Branch ${answer}: target=${targetIndex}`);
                if (cardIdMapping[targetIndex] !== undefined) {
                    console.log(`    ✓ Found in cardIdMapping: ${cardIdMapping[targetIndex]}`);
                } else {
                    console.log(`    ✗ NOT FOUND in cardIdMapping! Branch will be saved as NULL`);
                    console.log(`    Problem: targetIndex ${targetIndex} refers to UI card #${targetIndex + 1}, but cardIdMapping only has indices 0-${Object.keys(cardIdMapping).length - 1}`);
                }
            }
        });
    }
});

console.log('\n=== CORRECT SOLUTION ===');
console.log('1. saveSvaip should use question-only index for layout keys');
console.log('2. Branches need to be converted from UI indices to question-only indices before sending to backend');
console.log('3. formatFlowData should use question card counter, not overall index');

console.log('\nExample correct conversion:');
console.log('UI Card 0 (question, UI index 0) -> question index 0');
console.log('UI Card 1 (end) -> NOT in question array');
console.log('UI Card 2 (question, UI index 2) -> question index 1');
console.log('Branch [null, 2] should become [null, 1] before sending to backend');

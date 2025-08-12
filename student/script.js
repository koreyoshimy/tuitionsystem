// script.js




const manualIdInput = document.getElementById('manual-id');
const manualSubmitButton = document.getElementById('manual-submit');

manualSubmitButton.addEventListener('click', () => {
    const manualId = manualIdInput.value;
    // Send manual ID to backend for processing
    console.log('Manual ID:', manualId);
});
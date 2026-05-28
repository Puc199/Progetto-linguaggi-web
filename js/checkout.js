document.addEventListener('DOMContentLoaded', () => {
    let secondsLeft = 300; //tempo del timer
    //timer 
    const timerEl = document.getElementById('checkout-timer');
    const timerBox = document.getElementById('checkout-timer-box'); 
    const confirmBtn = document.getElementById('checkout-confirm-btn');

    function updateTimer() { //aggiorna il testo del timer e controlla se è scaduto
        const min = Math.floor(secondsLeft / 60);
        const sec = secondsLeft % 60;

        if (timerEl) {
            timerEl.textContent =
                String(min).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
        }
        //caso di tempo scadito
        if (secondsLeft <= 0) {
            clearInterval(timerInterval); //ferma il conto alla rovescia

            if (timerBox) {
                timerBox.classList.add('expired'); //cambio dello stile
            }

            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Tempo scaduto'; // il pulsante di conferma cambia
            }

            return;
        }

        secondsLeft--; //aggiornamento secondi
    }

    updateTimer(); //funzione di update timer 
    const timerInterval = setInterval(updateTimer, 1000); //ricorsiva, ogni secondo
});
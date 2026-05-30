document.addEventListener('DOMContentLoaded', function () {

    const walletForm = document.getElementById('wallet-form');
    const walletMessage = document.getElementById('wallet-message');
    const walletSaldo = document.getElementById('wallet-saldo');
    const walletSaldoTop = document.getElementById('wallet-saldo-top');
    const toggleHiddenBtn = document.getElementById('toggle-hidden-btn');
    const ticketsGrid = document.querySelector('.tickets-grid');
    const ticketsSection = document.querySelector('.tickets-section');

    //GESTIONE RICARICA WALLET
    if (walletForm) {
        walletForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(walletForm);

            try {
                const response = await fetch('ricarica_wallet_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                walletMessage.style.display = 'block';
                walletMessage.textContent = data.message || 'Operazione completata.';

                if (data.success) {
                    walletMessage.className = 'wallet-message success-box';
                    if (data.nuovo_saldo) {
                        const testoSaldo = '€ ' + data.nuovo_saldo;
                        if (walletSaldo) walletSaldo.textContent = testoSaldo;
                        if (walletSaldoTop) walletSaldoTop.textContent = testoSaldo;
                    }
                    walletForm.reset();
                } else {
                    walletMessage.className = 'wallet-message error-box';
                }
            } catch (error) {
                walletMessage.style.display = 'block';
                walletMessage.className = 'wallet-message error-box';
                walletMessage.textContent = 'Errore di comunicazione con il server.';
            }
        });
    }


    //GESTIONE ELIMINAZIONE BIGLIETTO

    document.querySelectorAll('.delete-ticket-btn').forEach(button => {
        button.addEventListener('click', async function () {
            const ticketId = this.dataset.ticketId;
            if (!ticketId) return;
            if (!confirm('Vuoi davvero eliminare questo biglietto e ricevere il rimborso?')) return; // messaggio di conferma per l'admin

            try {
                const response = await fetch('delete_ticket.php', { // Effettua la richiesta di eliminazione al server
                    method: 'POST', // Usa POST per inviare i dati in modo sicuro
                    headers: { 'Content-Type': 'application/json' }, // Specifica che stiamo inviando JSON
                    body: JSON.stringify({ id: ticketId }) // Invia l'ID del biglietto da eliminare al server
                });
                const data = await response.json();

                if (!data.success) {
                    alert(data.message || 'Operazione non riuscita.');
                    return;
                }

                const card = document.getElementById('ticket-card-' + ticketId);
                if (card) {
                    card.remove();
                    // Pulizia localStorage
                    let hidden = JSON.parse(localStorage.getItem('hiddenTickets') || '[]');
                    hidden = hidden.filter(id => id != ticketId);
                    localStorage.setItem('hiddenTickets', JSON.stringify(hidden));
                }

                if (data.nuovo_saldo) {
                    const testoSaldo = '€ ' + data.nuovo_saldo;
                    if (walletSaldo) walletSaldo.textContent = testoSaldo;
                    if (walletSaldoTop) walletSaldoTop.textContent = testoSaldo;
                }

                alert(data.message || 'Biglietto eliminato con successo.');
            } catch (error) {
                alert('Errore di comunicazione con il server.');
            }
        });
    });


    //NOTIFICA RIMBORSO (Dati iniettati da PHP)

const rimborsiEl = document.getElementById('rimborsi-data');

if (rimborsiEl) {
    try {
        const rimborsiRaw = rimborsiEl.dataset.rimborsi;
        const rimborsiDaNotificare = JSON.parse(rimborsiRaw || '[]');

        rimborsiDaNotificare.forEach(biglietto => {
            const storageKey = 'rimborso_visto_' + biglietto.rimborso_key;

            if (!localStorage.getItem(storageKey)) {
                const dataFormattata = new Date(biglietto.data_evento).toLocaleString('it-IT', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                alert(
                    `🔔 NOTIFICA RIMBORSO:\n\nL'evento "${biglietto.nome_evento}" del ${dataFormattata} è stato annullato.\nIl rimborso è stato accreditato sul tuo Wallet.`
                );

                localStorage.setItem(storageKey, 'true');
            }
        });
    } catch (e) {
        console.error('Errore nella lettura dei rimborsi:', e);
    }
}
    //NASCONDI / MOSTRA BIGLIETTI (Client-Side)

    let hiddenTickets = JSON.parse(localStorage.getItem('hiddenTickets') || '[]');

    // Funzione per mostrare/nascondere la empty-card quando tutti i biglietti sono nascosti
    function updateEmptyCardVisibility() {
        const allCards = document.querySelectorAll('.ticket-card');
        const visibleCards = document.querySelectorAll('.ticket-card:not([style*="display: none"])');
        
        // Rimuovi eventuali empty-card dinamiche già presenti
        const existingEmpty = ticketsSection?.querySelector('.empty-card-dynamic');
        if (existingEmpty) existingEmpty.remove();

        if (allCards.length > 0 && visibleCards.length === 0) {
            // Tutti nascosti → mostra empty-card
            if (ticketsGrid) ticketsGrid.style.display = 'none';
            
            const emptyCard = document.createElement('div');
            emptyCard.className = 'empty-card empty-card-dynamic';
            emptyCard.innerHTML = `
                <h3>Nessun biglietto visibile</h3>
                <p>Hai nascosto tutti i biglietti. Clicca sul pulsante sotto per mostrarli di nuovo.</p>
                <button type="button" id="show-all-from-empty" class="hero-cta" style="border:none;cursor:pointer;background:#0b5f97;color:white;padding:10px 20px;border-radius:6px;">
                     Mostra tutti i biglietti
                </button>
            `;
            ticketsSection?.appendChild(emptyCard);

            // Listener per il pulsante "Mostra tutti"
            document.getElementById('show-all-from-empty')?.addEventListener('click', function() {
                localStorage.removeItem('hiddenTickets');
                hiddenTickets = [];
                document.querySelectorAll('.ticket-card').forEach(card => card.style.display = '');
                if (ticketsGrid) ticketsGrid.style.display = 'flex';
                emptyCard.remove();
                updateEmptyCardVisibility();
                if (toggleHiddenBtn) toggleHiddenBtn.style.display = 'none';
            });
        } else {
            // Ci sono biglietti visibili → nascondi empty-card e mostra griglia
            if (ticketsGrid) ticketsGrid.style.display = 'flex';
        }
    }

    // Funzione principale per applicare lo stato "nascosto"
    function applyHiddenState() {
        // Prima mostra tutte le card, poi nascondi quelle in lista
        document.querySelectorAll('.ticket-card').forEach(card => card.style.display = '');
        
        hiddenTickets.forEach(id => {
            const card = document.getElementById(`ticket-card-${id}`);
            if (card) card.style.display = 'none';
        });

        // Aggiorna visibilità pulsante globale
        if (toggleHiddenBtn) {
            toggleHiddenBtn.style.display = hiddenTickets.length > 0 ? 'inline-block' : 'none';
        }

        // Controlla se serve la empty-card
        updateEmptyCardVisibility();
    }

    // Listener per pulsanti " Nascondi" su singole card
    document.querySelectorAll('.hide-ticket-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const ticketId = this.dataset.ticketId;
            const card = document.getElementById(`ticket-card-${ticketId}`);
            if (card) {
                card.style.display = 'none';
                if (!hiddenTickets.includes(ticketId)) {
                    hiddenTickets.push(ticketId);
                    localStorage.setItem('hiddenTickets', JSON.stringify(hiddenTickets));
                }
                applyHiddenState();
            }
        });
    });

    // Listener per pulsante globale "Mostra biglietti nascosti"
    if (toggleHiddenBtn) {
        toggleHiddenBtn.addEventListener('click', function () {
            localStorage.removeItem('hiddenTickets');
            hiddenTickets = [];
            document.querySelectorAll('.ticket-card').forEach(card => card.style.display = '');
            if (ticketsGrid) ticketsGrid.style.display = 'flex';
            this.style.display = 'none';
            updateEmptyCardVisibility();
        });
    }

    // Esegui all'avvio della pagina
    applyHiddenState();
});
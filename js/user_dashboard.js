document.addEventListener('DOMContentLoaded', () => {
    console.log('user_dashboard.js caricato');

    const form = document.getElementById('wallet-recharge-form');
    const inputImporto = document.getElementById('wallet-importo');
    const submitBtn = document.getElementById('wallet-submit-btn');
    const feedbackBox = document.getElementById('wallet-feedback');
    const balanceElement = document.getElementById('wallet-balance');
    const walletDisplayBalance = document.getElementById('wallet-display-balance');

    function showFeedback(message, isSuccess) {
        if (!feedbackBox) return;
        feedbackBox.style.display = 'block';
        feedbackBox.textContent = message;
        feedbackBox.className = isSuccess ? 'success' : 'error';
    }

    if (form && inputImporto && submitBtn && feedbackBox && balanceElement) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const importo = parseFloat(inputImporto.value);

            if (isNaN(importo) || importo <= 0) {
                showFeedback('Inserisci un importo valido maggiore di 0.', false);
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Ricarica in corso...';

            try {
                const formData = new FormData();
                formData.append('importo', importo);

                const response = await fetch('ricarica_wallet_ajax.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    balanceElement.textContent = '€ ' + data.nuovo_saldo;

                    if (walletDisplayBalance) {
                        walletDisplayBalance.textContent = '€ ' + data.nuovo_saldo;
                    }

                    inputImporto.value = '';
                    showFeedback(data.message, true);
                } else {
                    showFeedback(data.message || 'Operazione non riuscita.', false);
                }
            } catch (error) {
                console.error('Errore wallet:', error);
                showFeedback('Errore di comunicazione con il server.', false);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Ricarica saldo';
            }
        });
    }

    window.toggleTicketDetails = function(id) {
        console.log('toggleTicketDetails', id);

        const card = document.getElementById('ticket-card-' + id);
        const details = document.getElementById('ticket-details-' + id);
        const hint = card ? card.querySelector('.ticket-expand-hint') : null;

        if (!card || !details) return;

        if (details.classList.contains('active')) {
            details.classList.remove('active');
            card.classList.remove('expanded');
            if (hint) hint.textContent = 'Clicca per vedere i dettagli';
        } else {
            document.querySelectorAll('.ticket-details.active').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.ticket-card.expanded').forEach(el => el.classList.remove('expanded'));
            document.querySelectorAll('.ticket-expand-hint').forEach(el => {
                el.textContent = 'Clicca per vedere i dettagli';
            });

            details.classList.add('active');
            card.classList.add('expanded');
            if (hint) hint.textContent = 'Clicca per chiudere i dettagli';
        }
    };

    window.deleteTicket = async function(ticketId, prezzo, buttonEl) {
        console.log('deleteTicket chiamata', { ticketId, prezzo, buttonEl });

        const btn = buttonEl;
        const originalText = btn ? btn.textContent : '';

        if (btn) {
            btn.disabled = true;
            btn.textContent = '⏳ Elaborazione in corso...';
        }

        try {
            console.log('Invio fetch a delete_ticket.php');

            const response = await fetch('delete_ticket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: ticketId })
            });

            console.log('Risposta HTTP ricevuta', response.status);

            const result = await response.json();
            console.log('Risposta JSON', result);

            if (result.success) {
                if (balanceElement && result.nuovo_saldo) {
                    balanceElement.textContent = '€ ' + result.nuovo_saldo;
                }

                if (walletDisplayBalance && result.nuovo_saldo) {
                    walletDisplayBalance.textContent = '€ ' + result.nuovo_saldo;
                }

                const card = document.getElementById('ticket-card-' + ticketId);

                if (card) {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(-20px)';

                    setTimeout(() => {
                        card.remove();
                        alert(
                            '✅ Biglietto eliminato.\n\n' +
                            'Rimborso di €' + result.rimborso + ' effettuato con successo.'
                        );
                        location.reload();
                    }, 300);
                } else {
                    location.reload();
                }
            } else {
                alert('❌ Errore: ' + (result.message || 'Operazione non riuscita.'));
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            }
        } catch (error) {
            console.error('Errore deleteTicket:', error);
            alert('⚠️ Errore di comunicazione col server.');
            if (btn) {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }
    };
});
document.addEventListener('DOMContentLoaded', function () {
    const walletForm = document.getElementById('wallet-form');
    const walletMessage = document.getElementById('wallet-message');
    const walletSaldo = document.getElementById('wallet-saldo');
    const walletSaldoTop = document.getElementById('wallet-saldo-top');

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

    document.querySelectorAll('.delete-ticket-btn').forEach(button => {
        button.addEventListener('click', async function () {
            const ticketId = this.dataset.ticketId;

            if (!ticketId) return;
            if (!confirm('Vuoi davvero eliminare questo biglietto e ricevere il rimborso?')) return;

            try {
                const response = await fetch('delete_ticket.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: ticketId })
                });

                const data = await response.json();

                if (!data.success) {
                    alert(data.message || 'Operazione non riuscita.');
                    return;
                }

                const card = document.getElementById('ticket-card-' + ticketId);
                if (card) {
                    card.remove();
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
});
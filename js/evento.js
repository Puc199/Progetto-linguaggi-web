
document.addEventListener('DOMContentLoaded', () => {
    const replicaButtons = document.querySelectorAll('.replica-button');
    const replicaRiepilogo = document.getElementById('replica-riepilogo');
    const sectorList = document.getElementById('sector-list');

    const purchaseSection = document.getElementById('purchase-section');
    const purchaseSettore = document.getElementById('purchase-settore');
    const purchasePrezzo = document.getElementById('purchase-prezzo');

    const purchaseForm = document.getElementById('purchase-form');
    const purchasePrezzoInput = document.getElementById('purchase-prezzo-input');
    const purchasePrezzoHidden = document.getElementById('purchase-prezzo-hidden');
    const purchasePostiDisplay = document.getElementById('purchase-posti-display');
    const purchasePostiHidden = document.getElementById('purchase-posti-hidden');
    const selectedEventoSettore = document.getElementById('selected-evento-settore');
    const seatGrid = document.getElementById('seat-grid');

    if (!replicaButtons.length || !sectorList) return;

    function resetPurchaseArea() {
        if (purchaseSection) purchaseSection.style.display = 'none';
        if (purchaseSettore) purchaseSettore.textContent = '';
        if (purchasePrezzo) purchasePrezzo.textContent = '';
        if (purchasePrezzoInput) purchasePrezzoInput.value = '';
        if (purchasePrezzoHidden) purchasePrezzoHidden.value = '';
        if (purchasePostiDisplay) purchasePostiDisplay.value = '';
        if (purchasePostiHidden) purchasePostiHidden.value = '';
        if (selectedEventoSettore) selectedEventoSettore.value = '0';
        if (seatGrid) seatGrid.innerHTML = '';
    }

    replicaButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const idReplica = button.dataset.replicaId;
            const labelReplica = button.dataset.replicaLabel || '-';

            replicaButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            if (replicaRiepilogo) {
                replicaRiepilogo.textContent = labelReplica;
            }

            resetPurchaseArea();

            sectorList.innerHTML = `
                <div class="empty-state">
                    <h3>Caricamento settori...</h3>
                    <p>Attendi qualche istante.</p>
                </div>
            `;

            try {
                const response = await fetch(`ajax_settori_replica.php?id_replica=${encodeURIComponent(idReplica)}`);
                const data = await response.json();

                if (!data.success || !Array.isArray(data.settori) || data.settori.length === 0) {
                    sectorList.innerHTML = `
                        <div class="empty-state">
                            <h3>Nessun settore disponibile</h3>
                            <p>Questa replica non ha settori prenotabili.</p>
                        </div>
                    `;
                    return;
                }

                sectorList.innerHTML = '';

                data.settori.forEach(settore => {
                    const card = document.createElement('div');
                    card.className = 'match-card';

                    const prezzoFormattato = Number.parseFloat(settore.prezzo || 0)
                        .toFixed(2)
                        .replace('.', ',');

                    card.innerHTML = `
                        <div class="match-card-top">
                            <span class="match-badge">${settore.nome_settore}</span>
                            <span class="match-date">€ ${prezzoFormattato}</span>
                        </div>

                        <div class="match-details" style="padding-top: 18px;">
                            <h3>${settore.nome_settore}</h3>
                            <p>Posti disponibili: ${settore.posti_disponibili} / ${settore.posti_totali}</p>
                        </div>

                        <div class="match-card-bottom">
                            <button
                                type="button"
                                class="match-action sector-button"
                                data-settore-id="${settore.id}"
                                data-settore-nome="${settore.nome_settore}"
                                data-settore-prezzo="${settore.prezzo}"
                                data-settore-disponibili="${settore.posti_disponibili}"
                                data-settore-totali="${settore.posti_totali}"
                            >
                                Scegli questo settore
                            </button>
                        </div>
                    `;

                    sectorList.appendChild(card);
                });

            } catch (error) {
                sectorList.innerHTML = `
                    <div class="empty-state">
                        <h3>Errore di caricamento</h3>
                        <p>Non è stato possibile recuperare i settori della replica selezionata.</p>
                    </div>
                `;
            }
        });
    });

    sectorList.addEventListener('click', async (e) => {
        const button = e.target.closest('.sector-button');
        if (!button) return;

        e.preventDefault();

        const settoreId = button.dataset.settoreId;
        const settoreNome = button.dataset.settoreNome;
        const settorePrezzoRaw = button.dataset.settorePrezzo || '0';
        const settorePrezzoView = Number.parseFloat(settorePrezzoRaw).toFixed(2).replace('.', ',');
        const settoreTotali = parseInt(button.dataset.settoreTotali || '0', 10);

        if (purchaseSection) purchaseSection.style.display = 'block';
        if (purchaseSettore) purchaseSettore.textContent = settoreNome;
        if (purchasePrezzo) purchasePrezzo.textContent = `€ ${settorePrezzoView}`;
        if (purchasePrezzoInput) purchasePrezzoInput.value = `€ ${settorePrezzoView}`;
        if (purchasePrezzoHidden) purchasePrezzoHidden.value = settorePrezzoRaw;
        if (purchasePostiDisplay) purchasePostiDisplay.value = '';
        if (purchasePostiHidden) purchasePostiHidden.value = '';
        if (selectedEventoSettore) selectedEventoSettore.value = settoreId;

        if (seatGrid) {
            seatGrid.innerHTML = `<p>Caricamento posti...</p>`;
        }

        try {
            const response = await fetch(`ajax_posti_settore.php?id_evento_settore=${encodeURIComponent(settoreId)}`);
            const data = await response.json();

            const postiOccupati = (data.success && Array.isArray(data.posti_occupati))
                ? data.posti_occupati.map(Number)
                : [];

            if (seatGrid) {
                seatGrid.innerHTML = '';

                for (let i = 1; i <= settoreTotali; i++) {
                    if (postiOccupati.includes(i)) {
                        const span = document.createElement('span');
                        span.className = 'seat-pill seat-occupied';
                        span.textContent = `P${i}`;
                        seatGrid.appendChild(span);
                    } else {
                        const label = document.createElement('label');
                        label.className = 'seat-pill seat-available';
                        label.innerHTML = `
                            <input type="checkbox" value="${i}" class="seat-checkbox">
                            <span>P${i}</span>
                        `;
                        seatGrid.appendChild(label);
                    }
                }

                const seatCheckboxes = seatGrid.querySelectorAll('.seat-checkbox');

                seatCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', () => {
                        const selectedSeats = Array.from(
                            seatGrid.querySelectorAll('.seat-checkbox:checked')
                        ).map(el => el.value);

                        if (purchasePostiDisplay) {
                            purchasePostiDisplay.value = selectedSeats.join(', ');
                        }

                        if (purchasePostiHidden) {
                            purchasePostiHidden.value = selectedSeats.join(',');
                        }
                    });
                });
            }
        } catch (error) {
            if (seatGrid) {
                seatGrid.innerHTML = `<p>Errore nel caricamento dei posti.</p>`;
            }
        }

        purchaseSection?.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });

    if (purchaseForm) {
        purchaseForm.addEventListener('submit', function (e) {
            const checkedSeats = Array.from(
                document.querySelectorAll('#seat-grid .seat-checkbox:checked')
            ).map(el => el.value);

            if (!selectedEventoSettore || selectedEventoSettore.value === '0') {
                e.preventDefault();
                alert('Seleziona prima un settore.');
                return;
            }

            if (checkedSeats.length === 0) {
                e.preventDefault();
                alert('Seleziona almeno un posto.');
                return;
            }

            if (purchasePostiDisplay) {
                purchasePostiDisplay.value = checkedSeats.join(', ');
            }

            if (purchasePostiHidden) {
                purchasePostiHidden.value = checkedSeats.join(',');
            }
        });
    }
});
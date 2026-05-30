document.addEventListener('DOMContentLoaded', () => { 
    const buttons = document.querySelectorAll('.category-item'); // prendo tutti i pulsanti delle categorie
    const cards = document.querySelectorAll('.event-card'); // e tutte le card evento

    if (!buttons.length || !cards.length) return; // se non ci sono elementi esco

    buttons.forEach(button => {
        button.addEventListener('click', () => { // click per ogni pulsante
            const category = button.dataset.category; //leggo la categoria del pulsante

            buttons.forEach(btn => btn.classList.remove('active')); //tolgo active da tutti i pulsanti
            button.classList.add('active'); //la metto solo su quello cliccato

            cards.forEach(card => { //scorro tutte le card, se categoria è tutti li mostro tutti
                const cardCategory = card.dataset.category;
                card.style.display =
                    category === 'all' || cardCategory === category ? 'block' : 'none';
            });
        });
    });
});
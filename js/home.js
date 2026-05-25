document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.category-item');
    const cards = document.querySelectorAll('.match-card');

    if (!buttons.length || !cards.length) return;

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            const category = button.dataset.category;

            buttons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            cards.forEach(card => {
                const cardCategory = card.dataset.category;
                card.style.display =
                    category === 'all' || cardCategory === category ? 'block' : 'none';
            });
        });
    });
});
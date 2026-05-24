document.addEventListener('DOMContentLoaded', () => {

    // console.log("SCRIPT CARICATO"); //verifica

    /*const swiper = new Swiper('.swiper-container', {
        loop: true,
        spaceBetween: 30,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
    });
*/
    // FILTRO EVENTI

    const buttons = document.querySelectorAll('.category-item');
    const cards = document.querySelectorAll('.match-card');

    buttons.forEach(button => {

        button.addEventListener('click', () => {

            const category = button.dataset.category;

            cards.forEach(card => {

                const cardCategory = card.dataset.category;

                if (category === 'all' || cardCategory === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }

            });

        });

    });

});
window.onload = function() {
    fetch('session.php')
        .then(response => response.json())
        .then(data => {
            const userArea = document.getElementById('user-area');
            const loginLink = document.getElementById('login-link');
            if (data.logged_in && loginLink) {  //ho aggiunto && loginlink perchè nella console di devtool mi dava errore
                loginLink.style.display = 'none';
                const userNameElement = document.createElement('li');
                // Aggiungi un gestore di eventi onclick al link dell'username
                userNameElement.innerHTML = `<a href="#" class="username-style" onclick="checkRoleAndRedirect(${data.ruolo})">${data.username}</a><a href="logout.php" class="logout-style">Logout</a>`;
                userArea.appendChild(userNameElement);
            }
        });
};

// Funzione per verificare il ruolo e reindirizzare
function checkRoleAndRedirect(ruolo) {
    if (ruolo === 1) {
        window.location.href = 'admin_dashboard.php'; // Sostituisci con il link effettivo della pagina admin
    } else if (ruolo === 2) {
        window.location.href = 'ricarica_saldo.php'; // Sostituisci con il link effettivo della pagina utente
    }
}


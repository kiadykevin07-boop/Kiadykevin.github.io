async function showSection(sectionId) {
    document.querySelectorAll('div[id$="Section"]').forEach(div => div.classList.add('hidden'));
    document.getElementById(sectionId).classList.remove('hidden');
    document.querySelectorAll('.error').forEach(p => p.textContent = '');
}

async function login() {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const response = await fetch('api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    const data = await response.json();
    if (response.ok) {
        // Redirection vers le dashboard (vous devrez créer cette page)
        window.location.href = 'dashbord.html';
        
        // Stocker les informations utilisateur
        localStorage.setItem('userRole', data.role);
        localStorage.setItem('isLoggedIn', 'true');
        
    } else {
        document.getElementById('loginError').textContent = data.erreur || 'Erreur de connexion';
    }
}

async function register() {
    const username = document.getElementById('regUsername').value;
    const password = document.getElementById('regPassword').value;
    const email = document.getElementById('regEmail').value;
    const role = document.getElementById('regRole').value;
    const response = await fetch('api/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password, email, role })
    });
    const data = await response.json();
    if (response.ok) {
        showSection('loginSection');
        document.getElementById('loginError').textContent = 'Inscription réussie ! Vous pouvez vous connecter.';
    } else {
        document.getElementById('registerError').textContent = data.erreur || 'Erreur d\'inscription';
    }
}

async function forgotPassword() {
    const email = document.getElementById('forgotEmail').value;
    const response = await fetch('api/forgot-password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
    });
    const data = await response.json();
    if (response.ok) {
        showSection('resetPasswordSection');
        document.getElementById('resetError').textContent = 'Code envoyé à votre email !';
    } else {
        document.getElementById('forgotError').textContent = data.erreur || 'Erreur';
    }
}

async function resetPassword() {
    const email = document.getElementById('resetEmail').value;
    const token = document.getElementById('resetToken').value;
    const new_password = document.getElementById('resetPassword').value;
    const response = await fetch('api/reset-password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, token, new_password })
    });
    const data = await response.json();
    if (response.ok) {
        showSection('loginSection');
        document.getElementById('loginError').textContent = 'Mot de passe réinitialisé avec succès !';
    } else {
        document.getElementById('resetError').textContent = data.erreur || 'Erreur';
    }
}

// Fonctions pour le dashboard
function checkAuth() {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    if (!isLoggedIn && window.location.pathname.includes('dashbord.html')) {
        window.location.href = 'index.html';
    }
}

function setupDashboard() {
    const role = localStorage.getItem('userRole');
    const isAdmin = role === 'admin';
    
    // Afficher/masquer les contrôles admin
    document.getElementById('adminControls').classList.toggle('hidden', !isAdmin);
    document.getElementById('mouvementControls').classList.toggle('hidden', !isAdmin);
    document.getElementById('adminActionsHeader').classList.toggle('hidden', !isAdmin);
    
    // Charger les données
    loadProduits();
    loadMouvements();
}

async function loadProduits() {
    try {
        const response = await fetch('api/produits.php');
        const produits = await response.json();
        const tbody = document.querySelector('#tableProduits tbody');
        tbody.innerHTML = '';
        
        const isAdmin = localStorage.getItem('userRole') === 'admin';
        
        produits.forEach(produit => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${produit.id}</td>
                <td>${produit.nom}</td>
                <td>${produit.categorie}</td>
                <td>${produit.quantite}</td>
                <td>${produit.prix} Ar</td>
                <td>${isAdmin ? 
                    `<button onclick="updateProduit(${produit.id})">Modifier</button>
                     <button onclick="deleteProduit(${produit.id})">Supprimer</button>` : 
                    ''}
                </td>`;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erreur chargement produits:', error);
    }
}

async function addProduit() {
    const nom = document.getElementById('prodNom').value;
    const categorie = document.getElementById('prodCategorie').value;
    const quantite = document.getElementById('prodQuantite').value;
    const prix = document.getElementById('prodPrix').value;
    
    const response = await fetch('api/produits.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nom, categorie, quantite, prix })
    });
    
    if (response.ok) {
        loadProduits();
        clearProduitForm();
    }
}

async function updateProduit(id) {
    const nom = prompt('Nouveau nom:');
    const categorie = prompt('Nouvelle catégorie:');
    const quantite = prompt('Nouvelle quantité:');
    const prix = prompt('Nouveau prix:');
    
    if (nom && categorie && quantite && prix) {
        const response = await fetch('api/produits.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nom, categorie, quantite, prix })
        });
        
        if (response.ok) {
            loadProduits();
        }
    }
}

async function deleteProduit(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        const response = await fetch(`api/produits.php?id=${id}`, { method: 'DELETE' });
        if (response.ok) {
            loadProduits();
        }
    }
}

function clearProduitForm() {
    document.getElementById('prodNom').value = '';
    document.getElementById('prodCategorie').value = '';
    document.getElementById('prodQuantite').value = '';
    document.getElementById('prodPrix').value = '';
}

async function loadMouvements() {
    try {
        const response = await fetch('api/mouvements.php');
        const mouvements = await response.json();
        const tbody = document.querySelector('#tableMouvements tbody');
        tbody.innerHTML = '';
        
        mouvements.forEach(mouv => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${mouv.id}</td>
                <td>${mouv.produit_id}</td>
                <td>${mouv.type}</td>
                <td>${mouv.quantite}</td>
                <td>${new Date(mouv.date).toLocaleDateString()}</td>`;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erreur chargement mouvements:', error);
    }
}

async function addMouvement() {
    const produit_id = document.getElementById('mouvProduitId').value;
    const type = document.getElementById('mouvType').value;
    const quantite = document.getElementById('mouvQuantite').value;
    
    const response = await fetch('api/mouvements.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ produit_id, type, quantite })
    });
    
    if (response.ok) {
        loadMouvements();
        document.getElementById('mouvProduitId').value = '';
        document.getElementById('mouvQuantite').value = '';
    }
}

function logout() {
    localStorage.removeItem('userRole');
    localStorage.removeItem('isLoggedIn');
    window.location.href = 'index.html';
}

function showLogin() { showSection('loginSection'); }
function showRegister() { showSection('registerSection'); }
function showForgotPassword() { showSection('forgotPasswordSection'); }

// Initialisation
if (window.location.pathname.includes('dashbord.html')) {
    document.addEventListener('DOMContentLoaded', function() {
        checkAuth();
        setupDashboard();
    });
}
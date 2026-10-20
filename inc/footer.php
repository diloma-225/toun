<?php
 
?>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <img src="assets/img/logo-toun-blanc.png" alt="TOUN" class="footer-logo">
            <p>Le savoir qui transforme</p>
            <p class="footer-baseline">La plateforme qui accompagne les professionnels et les entreprises dans leur montée en compétences.</p>
            <div class="footer-social">
                <a href="#" aria-label="Facebook">📘</a>
                <a href="#" aria-label="LinkedIn">💼</a>
                <a href="#" aria-label="YouTube">▶️</a>
                <a href="#" aria-label="Instagram">📷</a>
            </div>
        </div>

        <div class="footer-col">
            <h4>Solutions</h4>
            <a href="entreprises.php">Entreprises</a>
            <a href="professionnels.php">Professionnels</a>
            <a href="campus.php">Campus virtuel</a>
            <a href="catalogue.php">Catalogue</a>
            <a href="toun-ai.php">TOUN AI</a>
        </div>

        <div class="footer-col">
            <h4>Ressources</h4>
            <a href="blog.php">Blog</a>
            <a href="aide.php">Centre d'aide</a>
            <a href="guides.php">Guides</a>
            <a href="webinaires.php">Webinaires</a>
            <a href="faq.php">FAQ</a>
        </div>

        <div class="footer-col">
            <h4>Entreprise</h4>
            <a href="a-propos.php">À propos</a>
            <a href="carrieres.php">Carrières</a>
            <a href="presse.php">Presse</a>
            <a href="partenaires.php">Partenaires</a>
        </div>

        <div class="footer-col">
            <h4>Légal</h4>
            <a href="cgu.php">Conditions générales</a>
            <a href="confidentialite.php">Politique de confidentialité</a>
            <a href="mentions-legales.php">Mentions légales</a>
            <a href="cookies.php">Cookies</a>
        </div>

        <div class="footer-col footer-contact">
            <h4>Contact</h4>
            <p>📍 Abidjan, Côte d'Ivoire</p>
            <p>📞 +225 07 12 34 56 78</p>
            <p>✉️ contact@toun-ci.com</p>
            <h4 class="footer-newsletter-title">Newsletter</h4>
            <p>Recevez nos nouveautés</p>
            <form class="footer-newsletter" action="newsletter.php" method="post">
                <input type="email" name="email" placeholder="Votre email" required>
                <button type="submit" aria-label="S'abonner">➤</button>
            </form>
        </div>
    </div>

    <div class="footer-bottom">
        © <?= date('Y') ?> TOUN. Tous droits réservés.
    </div>
</footer>

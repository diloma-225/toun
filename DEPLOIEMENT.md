# Déploiement avec Docker

Le site tourne dans deux conteneurs : `app` (PHP 8.3 + Apache) et `db` (MySQL 8.0).
Il faut Docker et Docker Compose sur le serveur.

## Première installation

1. Récupérer le code sur le serveur :

   ```bash
   git clone https://github.com/diloma-225/toun.git
   cd toun
   ```

2. Envoyer le dump de la base depuis le PC (il n'est pas dans Git) :

   ```bash
   scp bd_toun.sql utilisateur@serveur:~/toun/docker/initdb/
   ```

3. Créer le fichier `.env` et le remplir (mots de passe de la base, adresse du site, clés de paiement) :

   ```bash
   cp .env.example .env
   nano .env
   ```

4. Construire et lancer :

   ```bash
   docker compose up -d --build
   ```

Le site répond sur `http://IP_DU_SERVEUR:8080` (port réglable avec `APP_PORT`).

## Mettre à jour le site

```bash
git pull
docker compose up -d --build
```

## À savoir

- **Base de données** : `docker/initdb/bd_toun.sql` n'est importé qu'au tout premier démarrage, quand le volume `db_data` est vide. Les mises à jour suivantes ne touchent pas aux données.
- **Fichiers envoyés** (photos, vidéos, ressources) : ils sont conservés dans le volume `uploads`, même après une mise à jour.
- **HTTPS** : placer le site derrière un reverse proxy (Nginx, Caddy, Traefik…) qui transmet l'en-tête `X-Forwarded-Proto`. Les adresses de retour de paiement passent alors en `https`.
- **Emails** : le conteneur n'a pas de serveur mail, donc les réponses envoyées depuis l'admin (`admin/voir_message.php`) ne partent pas.
- **Sauvegarder la base** :

  ```bash
  docker compose exec db sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" bd_toun' > sauvegarde.sql
  ```

- **Logs** en cas de problème : `docker compose logs -f app`

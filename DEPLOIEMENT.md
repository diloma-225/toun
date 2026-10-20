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

### Automatiquement depuis GitHub

Après la configuration ci-dessous, chaque `git push` sur `main` met le site à jour.
Le suivi se fait dans l'onglet **Actions** du dépôt, et **Actions → Déploiement → Run workflow** relance un déploiement à la main.

1. Sur le PC, créer une clé SSH réservée au déploiement :

   ```bash
   ssh-keygen -t ed25519 -f toun_deploiement -N "" -C "github-actions"
   ```

2. Autoriser cette clé sur le serveur :

   ```bash
   cat toun_deploiement.pub | ssh utilisateur@serveur "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"
   ```

   L'utilisateur du serveur doit pouvoir lancer `docker` sans `sudo` (`sudo usermod -aG docker utilisateur`).

3. Sur GitHub, dans **Settings → Secrets and variables → Actions → New repository secret**, créer :

   | Secret | Valeur |
   |---|---|
   | `SSH_HOST` | IP ou nom du serveur |
   | `SSH_USER` | utilisateur SSH |
   | `SSH_KEY` | contenu du fichier `toun_deploiement` (clé privée) |
   | `SSH_PORT` | facultatif, `22` par défaut |
   | `DEPLOY_PATH` | facultatif, `~/toun` par défaut |

4. Supprimer ensuite `toun_deploiement` du PC : la clé privée n'a plus besoin d'être ailleurs que dans GitHub.

Tant que les secrets ne sont pas créés, le déploiement est simplement ignoré.

### À la main

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

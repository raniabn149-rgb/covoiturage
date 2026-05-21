# Plateforme de covoiturage

Ce projet est une application web de covoiturage développée en PHP, MySQL, HTML et CSS.

## Fonctionnalités
- Inscription et connexion sécurisées
- Publication de trajets
- Recherche de trajets
- Réservation et annulation
- Consultation de ses trajets et réservations

## Installation
1. Importez le fichier `db/database.sql` dans MySQL.
2. Placez le projet dans le dossier de votre serveur local (WAMP, XAMPP, etc.).
3. Mettez à jour les paramètres de la base de données dans `includes/config.php` si nécessaire.
4. Ouvrez le site via votre navigateur, par exemple `http://localhost/devphp`.

## Pages principales
- `index.php`
- `login.php`
- `register.php`
- `dashboard.php`
- `add_trip.php`
- `search.php`
- `my_trips.php`
- `my_bookings.php`

## Remarques
- Les mots de passe sont hachés avec `password_hash`.
- Les formulaires sont nettoyés et les requêtes utilisent des requêtes préparées pour limiter les injections SQL.
- Le style utilise Bootstrap 5 et un fichier CSS personnalisé.

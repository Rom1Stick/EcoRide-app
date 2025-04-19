# Configuration de l'application
APP_NAME=EcoRide
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

# Configuration de la base de données MySQL
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ecoride
DB_USERNAME=ecorider
DB_PASSWORD=securepass

# Configuration de la base de données SQLite
SQLITE_PATH=storage/data.sqlite

# Configuration MongoDB
NOSQL_URI=mongodb://mongo:changeme@mongodb:27017/ecoride_nosql

# Configuration de l'authentification
JWT_SECRET=votre_cle_secrete_jwt_tres_longue_et_complexe
JWT_EXPIRATION=3600

# Configuration du serveur SMTP pour les emails
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=no-reply@ecoride.fr
MAIL_FROM_NAME="${APP_NAME}"
# Kanban_System
composer install
php bin/console cache:clear --env=dev
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
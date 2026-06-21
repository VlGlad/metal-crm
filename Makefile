init: docker-down-clear docker-pull docker-build docker-up-d
up: docker-up-d

docker-up:
	docker compose up

docker-up-d:
	docker compose up -d

docker-down:
	docker compose down --remove-orphans

docker-down-clear:
	docker compose down -v --remove-orphans

docker-pull:
	docker compose pull

docker-build:
	docker compose build --build-arg UID=`id -u`

composer-install:
	docker compose exec php composer install

db-migrate:
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

db-migrate-diff:
	docker compose exec php php bin/console doctrine:migrations:diff

db-migrate-prev:
	docker compose exec php php bin/console doctrine:migrations:migrate prev

cache:
	docker compose exec php php bin/console cache:clear --no-interaction

create-admin:
	docker compose exec php php bin/console app:user:create-admin


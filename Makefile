help: ## See help
	@printf "\033[33m%s:\033[0m\n" 'Напишите: make <команда> где <команда> одна из следующих'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[32m%-18s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: docker-build ## Build docker images
up: docker-up ## Up docker
down: docker-down ## Down docker
php: docker-exec-php ## Enter to the php container

docker-down:
	docker-compose down --remove-orphans

docker-up:
	docker-compose up -d

docker-build:
	docker-compose build

composer: ## Install composer dependencies
	docker-compose exec backend composer install

docker-exec-php:
	docker-compose exec php bash
.PHONY: update
update:
	docker compose run --rm php8.2 composer update

.PHONY: tests
tests:
	docker compose run --rm php8.2
	docker compose run --rm php8.3
	docker compose run --rm php8.4
	docker compose run --rm php8.5

.PHONY: tests-unit
tests-unit:
	docker compose run --rm php8.2 composer tests:unit

.PHONY: tests-behat
tests-behat:
	docker compose run --rm php8.2 composer tests:behat

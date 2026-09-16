.PHONY: update
update:
	docker compose run --rm php8.3 composer update

.PHONY: scenarios-update
scenarios-update:
	docker compose run --rm php8.3 sh -c "git config --global --add safe.directory /app && composer install -q && composer scenario:update"

.PHONY: tests
tests:
	@for php in 8.3 8.4 8.5; do \
		for scenario in behat3 behat4; do \
			echo "=== PHP $$php / $$scenario ==="; \
			docker build --build-arg PHP_VERSION=$$php --build-arg SCENARIO=$$scenario \
				-t behat-fail-aid:$$php-$$scenario . \
				&& docker run --rm behat-fail-aid:$$php-$$scenario ./bin/run-tests.sh \
				|| exit 1; \
		done; \
	done

.PHONY: tests-unit
tests-unit:
	docker compose run --rm php8.3 composer tests:unit

.PHONY: tests-behat
tests-behat:
	docker compose run --rm php8.3 composer tests:behat

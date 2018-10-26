# I like the fish shell, and it has the very nice "string command", so
# why not use it?
SHELL=/usr/bin/fish

.PHONY: build relase coverage coverage-ci

build:
	composer install --no-dev
	./box build
	# Restore dev dependencies.
	composer install

release:
	@if string match -qrv '^[0-9]+\.[0-9]+\.[0-9]+$$' $(version); \
	  echo -e "\nPlease spercify version in X.Y.Z format\n"; \
          false; \
	end
	@if git rev-parse $(version) >/dev/null 2>&1; \
	  echo -e "\nVersion $(version) already exists\n"; \
          false; \
	end
	@echo "Updating readme"
	@sed -i -e 's/\\/[^/]*\\/dais-.*.phar/\\/$(version)\\/dais-$(version).phar/' README.md
	@echo "Updating changlog"
	@sed -i -e '/## Unreleased/a \\\n## $(version) - $(shell date +%F)' CHANGELOG.md
	@echo "Tagging"
	@git add -u
	@git commit -m"Release $(version)"
	@git tag $(version)

coverage:
	@phpdbg -qrr ./vendor/bin/phpspec run -c .phpspec.coverage.yml

coverage-ci:
	@phpdbg -qrr ./vendor/bin/phpspec run -c .phpspec.coverage-ci.yml

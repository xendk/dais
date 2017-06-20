
build:
	composer install --no-dev
	./box build
	# Restore dev dependencies.
	composer install

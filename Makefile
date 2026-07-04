dev:
	symfony serve & symfony console tailwind:build --watch

# make dev

prod:
	APP_ENV=prod symfony console tailwind:build --minify
	APP_ENV=prod symfony serve --no-tls
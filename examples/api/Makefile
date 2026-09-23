PORT=8000

default:
	@echo 'Targets:'
	@echo '  depend  -- Install Flight PHP dependencies'
	@echo '  serve   -- Run the service locally'

depend:
	composer install

serve:
	php -S localhost:$(PORT)
web: php artisan serve --host=0.0.0.0 --port=$PORT
worker: php artisan queue:work --queue=whatsapp-inbound,whatsapp-outbound,bot-engine,ai-suggest,default --sleep=3 --tries=3
reverb: php artisan reverb:start --host=0.0.0.0 --port=${REVERB_PORT:-8080} --no-interaction

#!/usr/bin/sh
# this file is used to start the container
# it will create a supervisord config file based on env variable CONTAINER_TYPE
# if Contianer type is not set, it will default to "monolith"
# our system is based on 2 parts the job workers and the main server with a scheduler
# type "worker" it will start only the job workers (php artisan queue:work)
# type "monolith" it will start the main server (php-fpm) and the scheduler (php artisan schedule:run) and the job workers

JOB_WORKER_SUPERVISOR_CONF="
[program:job-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /app/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=3
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/worker.log
stdout_logfile_maxbytes=500kb
stdout_logfile_backups=10

"

# a cron job to run the scheduler every minute
SCHEDULER_SUPERVISOR_CONF="
[program:scheduler]
command=php /app/artisan schedule:work --silent
autostart=true
autorestart=true

"

SERVER_SUPERVISOR_CONF="
[program:send-it]
command=php /app/artisan octane:start
autostart=true
autorestart=true

"

if [ -z "$CONTAINER_TYPE" ]; then
  CONTAINER_TYPE="monolith"
fi

echo "Starting container with type: $CONTAINER_TYPE"
SUPERVISOR_CONF="/etc/supervisor/conf.d/supervisord.conf"
echo "[supervisord]" > $SUPERVISOR_CONF
echo "nodaemon=true" >> $SUPERVISOR_CONF
echo "" >> $SUPERVISOR_CONF

case "$CONTAINER_TYPE" in
  "worker")
    echo "Starting job workers only"
    echo "$JOB_WORKER_SUPERVISOR_CONF" >> $SUPERVISOR_CONF
    ;;
  "monolith")
    echo "Starting main server, scheduler and job workers"
    echo "$SERVER_SUPERVISOR_CONF" >> $SUPERVISOR_CONF
    echo "$SCHEDULER_SUPERVISOR_CONF" >> $SUPERVISOR_CONF
    echo "$JOB_WORKER_SUPERVISOR_CONF" >> $SUPERVISOR_CONF
    ;;
  *)
    echo "Unknown CONTAINER_TYPE: $CONTAINER_TYPE"
    echo "Exiting..."
    exit 1
    ;;
esac

# start supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

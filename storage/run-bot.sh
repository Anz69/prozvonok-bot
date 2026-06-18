#!/bin/sh
cd "$(dirname "$0")/.."
while true; do
  php artisan nutgram:run >> storage/logs/bot.log 2>&1
  echo "[$(date)] nutgram:run exited, restart in 1s" >> storage/logs/bot.log
  sleep 1
done

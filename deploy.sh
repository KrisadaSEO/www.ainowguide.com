#!/bin/bash
set -e

echo "Starting deployment..."

cd /home/webserver005/public_html/ainowguide.com

git fetch origin main
git reset --hard origin/main

chmod +x deploy.sh

echo "Deployment finished."
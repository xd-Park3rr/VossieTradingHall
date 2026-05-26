#!/bin/bash
# Azure App Service startup script — runs on every container start
# Set this as the "Startup Command" in Azure App Service Configuration.
# Command to use:  /home/site/wwwroot/startup.sh

# Ensure uploads directory exists and is writable
mkdir -p /home/site/wwwroot/uploads
chmod 755 /home/site/wwwroot/uploads

# Start Apache (required when overriding the startup command)
apache2-foreground

#!/bin/bash
docker compose down
docker volume rm syncfire_wordpress_data
docker image rm syncfire-wordpress

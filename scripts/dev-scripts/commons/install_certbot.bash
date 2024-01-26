#!/bin/bash
set -e

# https://certbot.eff.org/#ubuntutrusty-apache

sudo apt-get update
sudo apt-get install software-properties-common
sudo add-apt-repository ppa:certbot/certbot
sudo apt-get update
sudo apt-get install python-certbot-apache
sudo certbot --apache

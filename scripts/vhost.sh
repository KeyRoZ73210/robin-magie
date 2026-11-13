#!/bin/bash

# Usage : vhost.sh NOM_DU_SITE CHEMIN_DU_DOSSIER_WEB
WEBSITE=$1
DOCROOT=${2:-"/Applications/MAMP/htdocs/$WEBSITE/web"}
VHOSTS_CONF="/Applications/MAMP/conf/apache/extra/httpd-vhosts.conf"
HOSTS_FILE="/etc/hosts"

echo "Configuration du virtual host..."

# Relancer le setup ne doit pas ajouter le même site deux fois.
if ! grep -q "ServerName $WEBSITE.test" "$VHOSTS_CONF" 2>/dev/null; then
  printf "\n<VirtualHost *:80>\n    DocumentRoot \"%s\"\n    ServerName %s.test\n</VirtualHost>" "$DOCROOT" "$WEBSITE" >> "$VHOSTS_CONF"
fi

if ! grep -q "[[:space:]]$WEBSITE.test\$" "$HOSTS_FILE"; then
  echo "127.0.0.1       $WEBSITE.test" | sudo tee -a "$HOSTS_FILE"
fi

echo "Virtual host $WEBSITE.test configuré !"
echo "Redémarre MAMP pour appliquer les changements."

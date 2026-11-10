#!/bin/sh
# First start: install TYPO3, German backend, a page with three "Dienstabende" elements. Then run Apache.
set -e
cd /var/www/project

if [ ! -f config/system/settings.php ]; then
  vendor/bin/typo3 setup -n --force --driver=sqlite --server-type=apache \
    --admin-username=admin --admin-user-password='Dienstabend#2026' --admin-email=admin@example.org \
    --project-name='DRK Dienstabende Test' --create-site='http://localhost:8080/'
  # Local test only: accept any host name, show errors.
  cat > config/system/additional.php <<'PHP'
<?php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
$GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = 1;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lang']['availableLanguages'] = ['de'];
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['drk_dienstabende']['allowedHosts'] = 'localhost';
PHP
  sed -i -e "s/title: English/title: Deutsch/" -e "s/en_US.UTF-8/de_DE.UTF-8/" -e "s/navigationTitle: English/navigationTitle: Deutsch/" -e "s/flag: us/flag: de/" config/sites/*/config.yaml
  vendor/bin/typo3 language:update de || echo 'German labels not downloaded, backend stays English'

  flex() { # url display limit moreLink
    printf '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="url"><value index="vDEF">%s</value></field><field index="display"><value index="vDEF">%s</value></field><field index="limit"><value index="vDEF">%s</value></field><field index="moreLink"><value index="vDEF">%s</value></field></language></sheet></data></T3FlexForms>' "$1" "$2" "$3" "$4"
  }
  url=http://localhost/dienstabende.json
  now=$(date +%s)
  sqlite3 var/sqlite/*.sqlite <<SQL
UPDATE be_users SET lang = 'de' WHERE username = 'admin';
UPDATE pages SET title = 'Bereitschaft Kreis Uelzen' WHERE uid = 1;
UPDATE sys_template SET config = 'page = PAGE
page.10 < styles.content.get' WHERE uid = 1;
INSERT INTO tt_content (pid, sorting, colPos, CType, header, header_layout, bodytext, pi_flexform, tstamp, crdate) VALUES
 (1, 256, 0, 'drkdienstabende_list', 'Hinweis: nächster Termin', 100, '<p>Interessierte sind ohne Anmeldung willkommen.</p>', '$(flex $url next 1 '')', $now, $now),
 (1, 512, 0, 'drkdienstabende_list', 'Nächste Dienstabende', 0, '<p>Unsere Ausbildungsabende sind offen für alle, die reinschnuppern möchten. Einfach vorbeikommen – eine Anmeldung ist nicht nötig.</p>', '$(flex $url list 4 t3://page?uid=1)', $now, $now),
 (1, 768, 0, 'drkdienstabende_list', 'Alle Dienstabende (nach Monaten)', 0, '', '$(flex $url months 0 '')', $now, $now);
SQL
  chown -R www-data:www-data var config public/fileadmin public/typo3temp
fi

exec apache2-foreground

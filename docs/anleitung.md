# Anleitung: Dienstabende-Plugin veröffentlichen und einbauen

Diese Anleitung erklärt Schritt für Schritt, wie das Plugin zu den Leuten kommt, die es installieren,
und was danach im TYPO3-Backend zu tun ist. Technische Details stehen im [README](../README.md).

## Was das Plugin macht

Das Plugin ist ein neues Inhaltselement **„Dienstabende“** für TYPO3. Im Element trägt man eine
Internetadresse ein, unter der die Termine als JSON-Datei liegen. Die Seite zeigt daraus die
kommenden Dienstabende als aufklappbare Liste, im Aussehen der DRK-Seiten.

![So sieht es auf der DRK-Seite aus](screenshots/5-im-drk-design.png)

## Überblick: Wer macht was?

| Schritt | Wer | Wie oft |
|---|---|---|
| 1. Paket bei Packagist veröffentlichen | du | einmal (erledigt) |
| 2. Neue Version herausgeben | du | bei jeder Änderung |
| 3. Plugin installieren und erlaubte Adresse eintragen | Betreiber der DRK-Plattform | einmal, danach Updates |
| 4. Element auf der Seite anlegen | Redaktion des Kreisverbands | einmal pro Seite |

## 1. Veröffentlicht: GitHub und Packagist

- Quelltext: <https://github.com/rubenvitt/typo3-drk-dienstabende>
- Paket: <https://packagist.org/packages/rubeen/drk-dienstabende>, installierbar mit
  `composer require rubeen/drk-dienstabende`
- ZIP-Datei für Installationen ohne Composer: unter **Releases** im GitHub-Repository

Packagist übernimmt neue Versionen von GitHub selbst. Steht auf der Paketseite einmal ein gelber
Hinweis „This package is not auto-updated“, unter <https://packagist.org/profile/> bei
„GitHub Hook Sync“ auf **Retry hook synchronization** klicken.

Die Versionen 0.1.0 bis 0.1.2 sind Zwischenstände ohne Release und sollten weg, damit niemand sie
installiert. Löschen kannst du sie so (fish), Packagist entfernt sie danach von selbst:

```fish
cd ~/dev/personal/drk/typo3-drk-dienstabende
git push origin :refs/tags/v0.1.0 :refs/tags/v0.1.1 :refs/tags/v0.1.2
git tag -d v0.1.0 v0.1.1 v0.1.2
```

## 2. Neue Version herausgeben

Wenn du etwas am Plugin geändert hast:

1. In `ext_emconf.php` die Versionsnummer erhöhen, z. B. von `0.1.3` auf `0.2.0`.
   Faustregel: letzte Stelle für Fehlerbehebungen, mittlere Stelle für neue Funktionen.
2. Committen und einen Tag mit derselben Nummer und einem `v` davor setzen (fish):

   ```fish
   cd ~/dev/personal/drk/typo3-drk-dienstabende
   git commit -am "Version 0.2.0: …"
   git tag -a v0.2.0 -m v0.2.0
   git push origin main v0.2.0
   ```

3. Fertig. GitHub prüft die Versionsnummer, baut die ZIP-Datei und legt das Release an
   (zu sehen unter **Actions** und **Releases** im Repository). Packagist übernimmt die Version
   automatisch.

Passt die Nummer in `ext_emconf.php` nicht zum Tag, bricht GitHub ab und es entsteht kein Release.
Dann Nummer korrigieren, Tag löschen und neu setzen:

```fish
git tag -d v0.2.0; git push origin :refs/tags/v0.2.0
```

## 3. Installation durch den Plattformbetreiber

Die Website des Kreisverbands läuft auf der zentralen DRK-CMS-Plattform. Plugins installiert dort
in der Regel der Betreiber, nicht der Kreisverband. Folgenden Text kannst du ihm schicken:

> **Betreff: TYPO3-Extension „Dienstabende“ für drk-uelzen.de**
>
> Wir möchten auf unserer Seite die offenen Dienstabende der Bereitschaft als Liste zeigen. Dafür
> gibt es eine kleine TYPO3-Extension (GPL, TYPO3 12.4 und 13.4):
>
> - Paket: `composer require rubeen/drk-dienstabende`
>   (Quelltext: https://github.com/rubenvitt/typo3-drk-dienstabende)
> - Danach `vendor/bin/typo3 extension:setup` und Caches leeren. Es gibt keine
>   Datenbanktabellen und kein TypoScript zum Einbinden.
> - In der Extension-Konfiguration `allowedHosts` setzen: `<Host der Termindaten>`.
>   Nur von dort ruft die Extension Daten ab (Port 80/443, keine Weiterleitungen).
> - Das Element wird ungecacht gerendert, die JSON-Daten werden 15 Minuten zwischengespeichert,
>   Abruf-Timeout 3 Sekunden. Ist die Quelle nicht erreichbar, bleibt die letzte Liste stehen.
>
> Ohne Composer gibt es die ZIP-Datei für den Extension-Manager unter
> https://github.com/rubenvitt/typo3-drk-dienstabende/releases.
>
> Könnt ihr uns sagen, welche TYPO3-Version die Plattform nutzt und ob ihr die Extension aufnehmen
> könnt?

`<Host der Termindaten>` ist nur der Servername ohne `https://` und ohne Pfad, z. B.
`termine.example.org`. Der steht erst fest, wenn klar ist, woher die Termine kommen (siehe
„Noch offen“ unten).

## 4. Element auf der Seite anlegen (Redaktion)

1. Im TYPO3-Backend links **Seite** wählen und die Seite „Bereitschaft Kreis Uelzen“ öffnen.
2. An der gewünschten Stelle **Neuen Inhalt erstellen** → Reiter **Plugins** → **Dienstabende**.

   ![Neues Element](screenshots/2-neues-element.png)
3. Überschrift eintragen, z. B. „Nächste Dienstabende“, und optional eine kurze Einleitung.
4. Im Abschnitt **Termine**:
   - **Adresse der Termindaten (JSON)**: die Adresse der Termindatei
   - **Darstellung**: meist „Aufklappbare Liste“. „Nach Monaten“ passt für eine eigene Unterseite,
     „Nur der nächste Termin“ für einen kurzen Hinweis oben auf der Seite.
   - **Höchstens so viele Termine**: z. B. 4, 0 zeigt alle
   - **Knopf „Alle Termine ansehen“**: optional ein Link auf eine Unterseite mit allen Terminen

   ![Element bearbeiten](screenshots/3-element-bearbeiten.png)
5. **Speichern** und mit **Anzeigen** prüfen.

Steht dort nur „Zurzeit sind keine Termine geplant.“, obwohl es Termine gibt, ist meistens der
Server der Termindatei nicht unter `allowedHosts` eingetragen. Das steht dann im TYPO3-Log.

Termine ändern sich auf der Seite von selbst, spätestens nach 15 Minuten.

## 5. Lokal ausprobieren

Zum Ausprobieren gibt es ein komplettes TYPO3 auf deinem Rechner (Docker muss laufen):

```fish
cd ~/dev/personal/drk/typo3-drk-dienstabende/testserver
docker compose up -d --build
```

Nach ein bis zwei Minuten:

- Website: <http://localhost:8080/>
- Backend: <http://localhost:8080/typo3/>, Benutzer `admin`, Passwort `Dienstabend#2026`

Die Beispieltermine stehen in `testserver/dienstabende.json`. Beenden mit `docker compose down`.
Der Testserver ist nur von deinem eigenen Rechner aus erreichbar.

## Noch offen

- **Woher kommen die Termine?** Das JSON-Format hat sich das Plugin selbst ausgedacht (siehe
  README). Liefert der DRKserver oder ein anderes System die Termine in einem anderen Format, muss
  nur eine Datei angepasst werden (`Classes/Domain/EventMapper.php`). Alternativ erzeugt ein
  kleines Skript aus der Quelle eine Datei im passenden Format und legt sie auf einen Webserver.
- **TYPO3-Version der Plattform:** getestet mit 12.4 und 13.4. Läuft die Plattform schon auf
  TYPO3 14, muss das Plugin dafür noch angepasst und getestet werden.
- **TYPO3 Extension Repository (TER):** Nur nötig, wenn andere Kreisverbände das Plugin im
  Extension-Manager suchen und finden sollen. Dafür braucht es ein Konto auf typo3.org und den
  reservierten Extension-Key `drk_dienstabende`. Für Uelzen allein reicht Packagist.

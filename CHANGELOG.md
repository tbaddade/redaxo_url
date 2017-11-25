
Url - Changelog
================================================================================

# Changelog

Version 1.0.1 - 06.11.2017
---------------------------------
### Bugfixes
- Hreflangs nur für aktive Sprache ausgeben (@DanielWeitenauer)
- Sprach Id in Relationstabellen wurden nicht beachtet (@TobiasKrais)

Version 1.0.0 - 27.10.2017
---------------------------------
### Neu
- Parser für Video Urls
- Update italienisches Sprachfile (@lexplatt)
- Update schwedisches Sprachfile (@interweave-media)
- SEO Auswahl eines Bildfeldes (@ynamite)
- neue SEO Methoden, die nur das Value liefern (@ynamite)

### Bugfixes
- leerer Suffix wurde nicht unterstützt
- Problem auf Strato Servern beseitigt (@TobiasKrais)

Version 1.0.0-beta5 - 31.07.2017
---------------------------------
### Neu
- Voraussetzungen angepasst:
    - REDAXO >= 5.3.0
    - YRewrite >= 2.2.0
- Update englisches Sprachfile (@ynamite)
- Update portugiesisches Sprachfile (@Taina Soares)

### Bugfixes
- Notices vermeiden und Sprach-Id immer setzen
- Defaultwert der Sprachen korrigiert
- Ids konnten nicht richtig aufgelöst werden, wenn ein Artikel mehrmals zugewiesen wurde
- Ein Problem mit UrlParamKey wurde gelöst
- Überprüfung ob field_name leer ist, führte ansonsten zur Überschreibung der Artikel-Urls (@lexplatt)

Version 1.0.0-beta4 - 12.09.2016
---------------------------------
### Neu
- Datenbanktabelle wird bei De-Installation gelöscht
- Methode getFullUrlById hinzugefügt
- Spalte für das letzte Update kann gesetzt werden und wird als lastmod in der Sitemap.xml verwendet
- getData wird jetzt als Objekt zurückgeben und beinhaltet noch zusätzliche Infos

### Bugfixes
- Fehler, wenn kein Rewriter installiert war #13

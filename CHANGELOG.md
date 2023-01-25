
# Changelog

## Version 2.1.0 - 25.01.2022

### Neu
- ExtensionPoint [URL_PROFILE_QUERY](https://github.com/tbaddade/redaxo_url/pull/262) für komplexere Manipulation des Queries (@DanielWeitenauer)
- Url\Seo setzt [Tags](https://github.com/tbaddade/redaxo_url/issues/254) via ExtensionPoint vom Rewriter, kein `new \Url\Seo()` im Template mehr notwendig
- Methode [`getCurrentUserPath()`](https://github.com/tbaddade/redaxo_url/issues/251) aufgenommen (@ynamite)
- Methode [`getUrlsAsKeyValuePair()`](https://github.com/tbaddade/redaxo_url/issues/245) aufgenommen 
- Doku; Beispiel ergänzt, um den Namespace des Profils einer Url zu erhalten (@alxndr-w)

### Bugfixes
- [#260](https://github.com/tbaddade/redaxo_url/issues/260) Beim Update wurde ein falscher Wert für die leere Relationstabelle gesetzt
- [#263](https://github.com/tbaddade/redaxo_url/issues/263) Entities vermeiden
- [#265](https://github.com/tbaddade/redaxo_url/issues/265) YForm Value wurde in der YForm-Datentabelle nicht mehr ausgegeben 
- [#267](https://github.com/tbaddade/redaxo_url/pull/267) isStructure() und isUserPath() lieferte immer false (@ynamite)


## Version 2.0.2 - 14.09.2022

### Bugfixes
- [#243](https://github.com/tbaddade/redaxo_url/pull/243) Readme angepasst - Best practice (@alxndr-w)
- [#247](https://github.com/tbaddade/redaxo_url/issues/247) doppelten Import von Version 1.x verhindern (@TobiasKrais)
- [#255](https://github.com/tbaddade/redaxo_url/pull/255) Code-Stabilität (@staabm)
- [#257](https://github.com/tbaddade/redaxo_url/pull/257) Deprecation-Notices im Vendor für PHP 8.1 beseitigt (@gharlan)


## Version 2.0.1 - 26.01.2022

### Bugfix
- Update funktionierte nicht (@TobiasKrais)


## Version 2.0.0 - 26.01.2022

### Neu
- Kompletter Umbau des Addons, sodass Urls jetzt in der Datenbank gespeichert werden

### Änderungen
- `URL_GENERATOR_PATH_CREATED` => `URL_MANAGER_PRE_SAVE`


## Version 1.0.1 - 06.11.2017

### Bugfixes
- Hreflangs nur für aktive Sprache ausgeben (@DanielWeitenauer)
- Sprach Id in Relationstabellen wurden nicht beachtet (@TobiasKrais)


## Version 1.0.0 - 27.10.2017

### Neu
- Parser für Video Urls
- Update italienisches Sprachfile (@lexplatt)
- Update schwedisches Sprachfile (@interweave-media)
- SEO Auswahl eines Bildfeldes (@ynamite)
- neue SEO Methoden, die nur das Value liefern (@ynamite)
- Voraussetzungen angepasst:
    - REDAXO >= 5.3.0
    - YRewrite >= 2.2.0
- Update englisches Sprachfile (@ynamite)
- Update portugiesisches Sprachfile (@Taina Soares)
- Datenbanktabelle wird bei De-Installation gelöscht
- Methode getFullUrlById hinzugefügt
- Spalte für das letzte Update kann gesetzt werden und wird als lastmod in der Sitemap.xml verwendet
- getData wird jetzt als Objekt zurückgeben und beinhaltet noch zusätzliche Infos

### Bugfixes
- leerer Suffix wurde nicht unterstützt
- Problem auf Strato Servern beseitigt (@TobiasKrais)
- Notices vermeiden und Sprach-Id immer setzen
- Defaultwert der Sprachen korrigiert
- Ids konnten nicht richtig aufgelöst werden, wenn ein Artikel mehrmals zugewiesen wurde
- Ein Problem mit UrlParamKey wurde gelöst
- Überprüfung ob field_name leer ist, führte ansonsten zur Überschreibung der Artikel-Urls (@lexplatt)
- Fehler, wenn kein Rewriter installiert war #13

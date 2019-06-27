Url AddOn
================================================================================
## Table of Contents
1. [Beschreibung](#beschreibung)
2. [Beispiel: News AddOn](#beispiel-news-addOn)
3. [Installation](#installation)
4. [unterstützte Rewriter](#unterstützte-rewriter)
5. [Extension Points](#extenstion_points)

## Beschreibung
--------------------------------------------------------------------------------
REDAXO 5 AddOn zur URL-Generierung für Daten aus den Datenbanktabellen (ehemals Url Control, ehemals Frau Schultze)

## Beispiel: Filme
--------------------------------------------------------------------------------
Normalerweise wird ein Film über eine Url wie `/filme/?movie_id=1` geholt.

Mit dem AddOn ist es möglich Urls wie `/filme/the-big-lebowski/` zu erzeugen.

Der REDAXO Artikel `/the-big-lebowski/` selbst existiert dabei nicht. Es wird alles im REDAXO Artikel `/filme/` abgehandelt.
**The Big Lebowski** ist dabei der Titel eines Filmes, welcher in einer eigenen Datenbanktabelle hinterlegt wurde. 

### Url holen 
Um die Url eines einzelnen Filmes auszugeben verwendet man:

```php
echo rex_getUrl('', '', ['movie-id' => $movieId]);
```

| Variable         | Beschreibung                 |
| ---------------- | ---------------------------- |
| `movie-id` | ist der im Profil hinterlegte Namensraum |
| `$movieId`    | Datensatz Id des Filmes |


### Id holen 
Nach dem der Film mit der eigenen Url aufgerufen wurde (Darstellung der Detailseite), muss jetzt die dazugehörige Datensatz-Id ermittelt werden. Erst dann können die eigentlichen Daten aus der Tabelle abgerufen und ausgegeben werden.

```php
// versuche die Url aufzulösen
$manager = Url\Url::resolveCurrent();
$movieId = $manager->getDatasetId();
```

### Beispiel Code

```php
<?php
use Url\Url;

$manager = Url::resolveCurrent();

if ($manager) {
    $movie = rex_yform_manager_table::get('rex_movie')->query()->findId($manager->getDatasetId());
    if ($movie) {
        dump($movie);
    }
} else {
    $movies = rex_yform_manager_table::get('rex_movie')->query()->find();
    if (count($movies)) {
        foreach ($movies as $movie) {
            echo '<a href="' . rex_getUrl('', '', ['movie-id' => $movie->getId()]) . '">' . $movie->getValue('title') . '</a>';
        }
    }
}
```

### zusätzliche Pfade aus Relationen bilden

Möchte man die Filme Genres zuordnen, passiert dies meist über eine Relation zu diesen Kategorien.
Die Urls dazu könnten dann so aussehen: `/filme/komoedie/the-big-lebowski/`
 

### zusätzliche Pfade für die Url

#### eigene Pfade an die Url hängen

Im Feld **eigene Pfade an die Url hängen** lassen sich zusätzliche Pfade eintragen, die als gültige Urls verwendet werden können. So ließe sich beispielsweise bei einem Film noch eine zusätzliche Seite über `/filme/the-big-lebowski/zitate/` anzeigen. Dann muss in dem Textfeld einfach nur `zitate` eingetragen werden - ohne vorangestellten und abschließenden Schrägstrich.

<del>Bei der Ausgabe kann man dann `$mypath = UrlGenerator::getCurrentOwnPath();` schreiben. Wenn die Seite mit dem Zusatz /info aufgerufen wird, enthält `$mypath` den Wert `info`.</del>


#### Unterkategorien anhängen?

Es können an die erzeugte Url auch die Unterkategorien des Artikels angehangen werden
Die Urls dazu könnten dann so aussehen: `/filme/the-big-lebowski/schauspieler/`

`Schauspieler` ist dabei eine Unterkategorie der Kategorie `Filme` innerhalb der Strukturverwaltung.

<del>
### Beispiel: URL-Pathlist neu generieren

Wenn Datenbanktabellen außerhalb des YForm-Table-Managers befüllt werden, greift der passende EP nicht und die URLs werden nicht neu generiert. Dies lässt sich manuell nachholen, indem folgende Methode aufgerufen wird.

```
UrlGenerator::generatePathFile([]);
```
</del>

### Installation
--------------------------------------------------------------------------------
* Via Install AddOn im Backend herunterladen
* AddOn installieren und aktivieren


### unterstützte Rewriter
--------------------------------------------------------------------------------
* [yrewrite](https://github.com/yakamara/redaxo_yrewrite)


## Extension Points
--------------------------------------------------------------------------------

Der Extension Point URL_MANAGER_PRE_SAVE gibt die Möglichkeit eine URL vor dem Speichern in der URL Tabelle zumanipulieren.

### Beispiel Code URL_MANAGER_PRE_SAVE

```php
<?php
rex_extension::register('URL_MANAGER_PRE_SAVE', 'rex_url_shortener');

/**
 * Kürzt URL für ALLE Profile indem es die Artikel und Kategorienamen aus der URL entfernt.
 * Es findet im Beispielcode KEINE Prüfung statt ob eine URL schon existiert. Beim Erstellen der Profile muss darauf
 * geachtet werden, dass doppelte URLs nicht möglich sind, da sonst beim Speichern ein Fatal Error geworfen wird.
 * @param rex_extension_point $ep Redaxo extension point
 * @return Url Neue URL
 */
function rex_url_shortener(rex_extension_point $ep) {
	$params = $ep->getParams();
	$url = $params['object'];
	$article_id = $params['article_id'];
	$clang_id = $params['clang_id'];
	
	// URL muss nur gekürzt werden, wenn es sich nicht im den Startartikel der Domain handelt
	if($article_id != rex_yrewrite::getDomainByArticleId($article_id, $clang_id)->getStartId()) {
		$article_url = rex_getUrl($article_id, $clang_id);
		$start_article_url = rex_getUrl(rex_yrewrite::getDomainByArticleId($article_id, $clang_id)->getStartId(), $clang_id);
		$article_url_without_lang_slug = '';
		if(strlen($start_article_url) == 1) {
            // Wenn lang slug  im Startartikel nicht angezeigt wird
			$article_url_without_lang_slug = str_replace('/'. strtolower(rex_clang::get($clang_id)->getCode()) .'/', '/', $article_url);
		}
		else {
			$article_url_without_lang_slug = str_replace($start_article_url, '/', $article_url);
		}
		$new_url = new \Url\Url(str_replace($article_url_without_lang_slug, '/', $url->__toString()));
		$new_url->handleRewriterSuffix();

		return $new_url;
	}
	
	return $url;
}
```

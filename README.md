Url AddOn
================================================================================
### Table of Contents
1. [Beschreibung](#beschreibung)
1. [Beispiel: News AddOn](#beispiel-news-addon)
1. [URL-Pathlist neu generieren](#url-pathlist-generieren)
1. [Installation](#installation)
1. [unterstützte Rewriter](#unterstützte-rewriter)
1. [SEO-Metadaten](#seo-metadaten)

### Beschreibung
--------------------------------------------------------------------------------
REDAXO 5 AddOn zur URL-Generierung für Daten aus den Datenbanktabellen (ehemals Url Control, ehemals Frau Schultze)

### Beispiel: News AddOn
--------------------------------------------------------------------------------
Normlerweise wird eine News über eine Url wie **/news.html?news_id=1** geholt.

Mit dem AddOn ist es möglich Urls wie **/news/news-title/** zu erzeugen.

Der REDAXO Artikel **/news-title/** selbst existiert dabei nicht. Es wird alles im REDAXO Artikel **/news/** abgehandelt.

#### Url holen 
Um die Url einer einzelnen News auszugeben verwendet man:

```php
$newsUrl = rex_getUrl($newsArticleId, $newsClangId, ['id' => $newsDataId]);
```

| Variable         | Beschreibung                 |
| ---------------- | ---------------------------- |
| `$newsArticleId` | Artikel Id des Detailartikel |
| `$newsClangId`   | Clang Id des Detailartikel   |
| `$newsDataId`    | Datensatz Id der News        |


#### Id holen 
Um die tatsächliche Id der einzelnen News zu erhalten, wird folgende Methode verwendet:

```php
$newsDataId = UrlGenerator::getId();
```

#### Beispiel Code

```php
<?php
$newsDataId = UrlGenerator::getId();
$newsArticleId = 5;

if ($newsDataId > 0) {
    $datas = rex_sql::factory()->getArray('SELECT * FROM news_table WHERE id = ?', [$newsDataId]);
    if (count($datas)) {
        $data = current($datas);
		echo $data['title'];
	}
} else {
    $datas = rex_sql::factory()->getArray('SELECT * FROM news_table');
    if (count($datas)) {
    	foreach ($datas as $data) {
			echo '<a href="' . rex_getUrl($newsArticleId, '', ['id' => $data['id']]) . '">' . $data['title'] . '</a>';
		}
	}
}
?>
```
#### Weitere Parameter
Weitere Parameter können über die Funktion getData geholt werden

```php
<?php
$urldata = UrlGenerator::getData();
dump($urldata);
?>
```

<a id="url-pathlist-generieren"></a>
### URL-Pathlist neu generieren
--------------------------------------------------------------------------------
Wenn Datenbanktabellen außerhalb des YForm-Table-Managers befüllt werden, greift der passende EP nicht und die URLs werden nicht neu generiert. Dies lässt sich manuell nachholen, indem folgende Methode aufgerufen wird.

```
UrlGenerator::generatePathFile([]);
```

<a id="installation"></a>
### Installation
--------------------------------------------------------------------------------
* Via Install AddOn im Backend herunterladen
* AddOn installieren und aktivieren


<a id="url-unterstützte-rewriter"></a>
### unterstützte Rewriter
--------------------------------------------------------------------------------
* [yrewrite](https://github.com/yakamara/redaxo_yrewrite)


<a id="url-unterstützte-rewriter"></a>
### SEO-Metadaten
--------------------------------------------------------------------------------

Für die ordnungsgemäße Ausgabe müssen die YRewrite-Tags für Titel, Beschreibung u.a. durch die SEO-Klasse des URL-Addons ausgetauscht werden:

```
$urlSeo = new UrlSeo();
    echo $urlSeo->getTitleTag().PHP_EOL;
    echo $urlSeo->getDescriptionTag().PHP_EOL;
    echo $urlSeo->getRobotsTag().PHP_EOL;
    echo $urlSeo->getHreflangTags().PHP_EOL;
    echo $urlSeo->getCanonicalUrlTag().PHP_EOL;
```

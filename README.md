Url AddOn
================================================================================
### Table of Contents
1. [Beschreibung](#beschreibung)
2. [Beispiel: News AddOn](#beispiel-news-addOn)
3. [Installation](#installation)
4. [unterstützte Rewriter](#unterstützte-rewriter)

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


### Installation
--------------------------------------------------------------------------------
* Via Install AddOn im Backend herunterladen
* AddOn installieren und aktivieren


### unterstützte Rewriter
--------------------------------------------------------------------------------
* [yrewrite](https://github.com/yakamara/redaxo_yrewrite)

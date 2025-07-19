# Url Add-on für REDAXO 5

> Fork von <https://github.com/tbaddade/redaxo_url/> mit dem Ziel, das Add-on kontinuierlich zu verbessern und Fehler schnell zu beheben und zu releasen.

## Beschreibung

REDAXO 5 Add-on, um aus Datensätzen in Tabellen SEO-freundliche URLs zu generieren.

## Features

* Generiert URLs anhand Datensätzen und eines REDAXO-Artikels, z.B.: `www.example.org/artikel/datensatz/` statt `www.example.org/artikel/?id=1`
* Verwendung von Relationen für Kategorien und Unterkategorien möglich, z.B.: `www.example.org/kategorie-datensatz/datensatz/`
* Mit und ohne YForm-Tabellen nutzbar
  * Erzeugt URLs aus YForm-Tabellen
  * Automatische Aktualisierung der URLs über Extension Points von YForm
* Integration in YRewrite
  * Zusätzliche Methoden für `<title />`-Felder, SEO- und OpenGraph-Metadaten wie `description` und `og:image`
  * Integration in die `sitemap.xml` von YRewrite
  * Unterstützt Multidomain-Konfiguration
  * Untersützt Mehrsprachigkeit (REDAXO clang)
* URLs werden vom Addon `search_it` erkannt und Inhalte indexiert

## Add-ons, die auf URL setzen

* <https://github.com/FriendsOfREDAXO/neues>
* <https://github.com/FriendsOfREDAXO/warehouse>
* <https://github.com/alexplusde/events>
* <https://github.com/alexplusde/staff>
* <https://github.com/alexplusde/stellenangebote>

u. v. a.

## Installation

Unter <https://github.com/alexplusde/url/releases> werden Releases dieses Add-ons bereitgestellt, diese entpacken und in den Ordner `src/addons/` kopieren.

> **Hinweis:** In einer kommenden Version soll auch eine Update-Funktion bzw. Benachrichtigungs-Funktion integriert werden.
>
> **Hinweis:** Bei Update reinstallieren.

## Beispiel: News

Normalerweise wird ein Film über eine Url wie `/news/?news_id=1` geholt.

Mit dem Add-on ist es möglich Urls wie `/news/hallo-welt/` zu erzeugen. Der REDAXO Artikel zu `/news/hallo-welt/` selbst existiert dabei nicht. Es wird alles im REDAXO Artikel `/news/` abgehandelt.

### Url generieren

Um die Url eines Eintrags auszugeben:

```php
$url_key = 'news-id'; // der Key, der innerhalb eines URL-Profils verwendet wird
echo rex_getUrl('', '', [$url_key => $newsId]); // generiert die Url /artikel-name/news-titel/
```

Tipp: Wenn du YOrm benutzt, erstelle in deiner Model-Klasse eine Methode `getUrl()`:

```php
// ...
public function getUrl()
{
    return rex_getUrl('', '', ['news-id' => $this->getId()]);
}
// ...
```

### Beispiel-Modul

Als Weiche zwischen der Detailseite und der Übersicht kann ein Modul verwendet werden, das die Url auflöst und dann entweder die Detailseite oder die Übersicht anzeigt.

```php
$manager = Url\Url::resolveCurrent();
if($manager !== null) {
    // Detailseite
    $news = rex_yform_manager_table::get('rex_news')->query()->findId($manager->getDatasetId());
    // oder wenn ModelClass genutzt wird
    $news = News::get($manager->getDatasetId());
    if ($news) {
        // einfach ausgeben, z.B. echo '<h1>' . $news->getValue('title') . '</h1>', oder:
        $fragment rex_fragment::factory();
        $fragment->setVar('dataset', $news, false);
        $fragment->setVar('title', $news->getValue('title'), false);
        $fragment->setVar('content', $news->getValue('content'), false);
        echo $fragment->parse('news-details.php');
    }
} else {
    // Übersicht
    $newsList = rex_yform_manager_table::get('rex_news')->query()->where('status', 0, '>')->find();
    // oder wenn ModelClass genutzt wird, $newsList = News::query()->find();
    foreach ($newsList as $news) {
        // einfach ausgeben, z.B. echo '<a href="' . rex_getUrl('', '', ['news-id' => $news->getId()]) . '">' . $news->getValue('title') . '</a><br>'; oder:
        $fragment = rex_fragment::factory();
        $fragment->setVar('dataset', $news, false);
        $fragment->setVar('title', $news->getValue('title'), false);
        $fragment->setVar('url', rex_getUrl('', '', ['news-id' => $news->getId()]), false);
        echo $fragment->parse('news-list-item.php');

    }
}
```

> **Tipp:** Die Verwendung von Fragmenten ist nicht notwendig, kann aber helfen, die Übersichtlichkeit zu verbessern.

### Id holen

Nachdem der Film mit der eigenen Url aufgerufen wurde (Darstellung der Detailseite), muss jetzt die dazugehörige Datensatz-Id ermittelt werden. Erst dann können die eigentlichen Daten aus der Tabelle abgerufen und ausgegeben werden.

```php
// versuche die Url aufzulösen
$manager = Url\Url::resolveCurrent();
if($manager !== null) {
    $news = $manager->getDataset(); // gibt eine Instanz der Model Class zurück, der der Url zugeordnet ist
    $newsId = $manager->getDatasetId(); // gibt die Id des Datensatzes zurück
}
```

### Erweitertes Modul-Beispiel

Es können auch mehrere Tabellen auf denselben Artikel gemappt werden, z.B. News-Kategorien und News-Einträge:

```php
<?php
use Url\Url;
$manager = Url::resolveCurrent();
if ($manager !== null) {
    $profile = $manager->getProfile();
    if ($profile->getTableName() === 'rex_news') {
        $news = rex_yform_manager_table::get('rex_news')->query()->findId($manager->getDatasetId());
        // oder wenn ModelClass genutzt wird
        $news = News::get($manager->getDatasetId());
        if ($news) {
            echo '<h1>' . $news->getValue('title') . '</h1>';
            echo '<p>' . $news->getValue('content') . '</p>';
        }
    } elseif ($profile->getTableName() === 'rex_news_category') {
        $category = rex_yform_manager_table::get('rex_news_category')->query()->findId($manager->getDatasetId());
        // oder wenn ModelClass genutzt wird
        $category = NewsCategory::get($manager->getDatasetId());
        if ($category) {
            echo '<h1>' . $category->getValue('name') . '</h1>';
            // Weitere Logik für Kategorien
        }
    }
} else {
    // Übersicht aller News
    $newsList = rex_yform_manager_table::get('rex_news')->query()->find();
    // oder wenn ModelClass genutzt wird
    $newsList = News::getAll();
    foreach ($newsList as $news) {
        echo '<a href="' . rex_getUrl('', '', ['news-id' => $news->getId()]) . '">' . $news->getValue('title') . '</a><br>';
    }
}
```

### Weitere Methoden des URL-Managers

```php
$manager = Url\Url::resolveCurrent();
if ($manager !== null) {
    // Gibt die Id des REDAXO-Artikels zurück, der mit der URL verknüpft ist
    $articleId = $manager->getArticleId();

    // Gibt die Id der Sprache (clang) zurück
    $clangId = $manager->getClangId();

    // Gibt die Id des Datensatzes zurück
    $datasetId = $manager->getDatasetId();

    // Gibt das zugehörige Profil-Objekt zurück
    $profile = $manager->getProfile();

    // Gibt den Namespace des Profils zurück, z.B. 'news'
    $namespace = $manager->getProfile()->getNamespace();

    // Gibt die SEO-Titel für die URL zurück
    $seoTitle = $manager->getSeoTitle();

    // Gibt die SEO-Beschreibung zurück
    $seoDescription = $manager->getSeoDescription();

    // Gibt das SEO-Bild zurück
    $seoImage = $manager->getSeoImage();

    // Gibt alle SEO-Werte als Array zurück
    $seo = $manager->getSeo();

    // Gibt das letzte Änderungsdatum zurück
    $lastmod = $manager->getLastmod();

    // Gibt die URL als String zurück
    $urlString = $manager->getUrl()->__toString();

    // Gibt das Url-Objekt zurück
    $url = $manager->getUrl();

    // Gibt zurück, ob die URL in der Sitemap erscheinen soll
    $inSitemap = $manager->inSitemap();

    // Gibt zurück, ob es sich um eine Struktur-URL handelt
    $isStructure = $manager->isStructure();

    // Gibt zurück, ob es sich um eine User-Path-URL handelt
    $isUserPath = $manager->isUserPath();

    // Gibt zurück, ob es sich um eine Root-URL handelt
    $isRoot = $manager->isRoot();

    // Gibt einen Wert aus dem internen Array zurück
    $value = $manager->getValue('keyname');
}
```

### Zusätzliche Pfade aus Relationen bilden

Möchte man den News-Einträgen auch eine Haupt-Kategorie zuordnen, ist dies über eine Relation möglich. Im YForm-Kontext wäre das eine Relation über ein `be_manager_relation`-Feld (1:n-Beziehung), z.B. `/news/category/title/`.

### Zusätzliche Pfade für die Url

#### eigene Pfade an die Url hängen

Im Feld **eigene Pfade an die Url hängen** lassen sich zusätzliche statische Pfade eintragen, die als gültige Urls verwendet werden können. So ließe sich beispielsweise bei einem News-Eintrag noch eine zusätzliche Seite über `/news/title/comments/` anzeigen. Dann muss in dem Textfeld einfach nur `comments` eingetragen werden - ohne vorangestellten und abschließenden Schrägstrich.

#### Unterkategorien der Struktur anhängen

Es können an die erzeugte Url auch die Unterkategorien des Artikels angehangen werden
Die Urls dazu könnten dann so aussehen: `/filme/news/autoren/`

`Autoren` ist dabei eine Unterkategorie der Kategorie `News` innerhalb der Strukturverwaltung.

### Beispiel: URLs neu generieren

Im YForm-Kontext werden URLs automatisch aktualisiert, wenn ein Datensatz erstellt, gespeichert oder gelöscht wird. Das Add-on bietet dafür einen passenden Extension Point, der sowohl bei Bearbeitung im Table Manager, als auch über YOrm greift.

Wenn Datenbanktabellen außerhalb des YForm-Table-Managers bearbeitet werden, z.B. durch `rex_sql` (nicht empfohlen), dann greift der passende Extension Point nicht und die URLs werden nicht neu generiert. Dies lässt sich mit folgendem Code für alle Profile oder ein gezieltes Profil überbrücken:

```php
$profiles = \Url\Profile::getAll();
if ($profiles) {
    foreach ($profiles as $profile) {
        $profile->deleteUrls();
        $profile->buildUrls();
    }
}
```

### Beispiel: Auslesen, über welchen Profil-Key der Artikel aufgerufen wurde / die URL generiert wurde

```php
$manager = Url::resolveCurrent();
if ($manager && $profile = $manager->getProfile()) {
    dump($profile->getNamespace());
}
```

## Extension Points

* URL_PRE_SAVE
* URL_PROFILE_RESTRICTION
* URL_SEO_TAGS
* URL_TABLE_UPDATED

### URL_PRE_SAVE

Der Extension Point `URL_PRE_SAVE` gibt die Möglichkeit eine URL vor dem Speichern in der URL Tabelle zu manipulieren.

#### Beispiel Code URL_PRE_SAVE

```php
rex_extension::register('URL_PRE_SAVE', 'rex_url_shortener');

/**
 * Kürzt URL für ALLE Profile indem es die Artikel und Kategorienamen aus der URL entfernt.
 * @param rex_extension_point $ep Redaxo extension point
 * @return Url Neue URL
 */
function rex_url_shortener(rex_extension_point $ep)
{
    $params = $ep->getParams();
    $url = $params['object'];
    $article_id = $params['article_id'];
    $clang_id = $params['clang_id'];

    // URL muss nur gekürzt werden, wenn es sich nicht im den Startartikel der Domain handelt
    if ($article_id != rex_yrewrite::getDomainByArticleId($article_id, $clang_id)->getStartId()) {
        $article_url = rex_getUrl($article_id, $clang_id);
        $start_article_url = rex_getUrl(rex_yrewrite::getDomainByArticleId($article_id, $clang_id)->getStartId(), $clang_id);
        $article_url_without_lang_slug = '';
        if (strlen($start_article_url) == 1) {
            // Wenn lang slug  im Startartikel nicht angezeigt wird
            $article_url_without_lang_slug = str_replace('/'.strtolower(rex_clang::get($clang_id)->getCode()).'/', '/', $article_url);
        } else {
            $article_url_without_lang_slug = str_replace($start_article_url, '/', $article_url);
        }

        // Im Fall $url ist urlencoded, muss Artikel URL ebenfalls encoded werden
        $article_url_without_lang_slug_split = explode("/", $article_url_without_lang_slug);
        for ($i = 0; $i < count($article_url_without_lang_slug_split); $i++) {
            $article_url_without_lang_slug_split[$i] = urlencode($article_url_without_lang_slug_split[$i]);
        }
        $article_url_without_lang_slug_split_encoded = implode("/", $article_url_without_lang_slug_split);

        $new_url = new \Url\Url(str_replace($article_url_without_lang_slug_split_encoded, '/', $url->__toString()));

        // Auf Duplikate prüfen
        $query = "SELECT * FROM ".\rex::getTablePrefix()."url_generator_url "
            ."WHERE url = '".$new_url->__toString()."'";

        $result = \rex_sql::factory();
        $result->setQuery($query);
        if ($result->getRows() > 0) {
            // FALSE zurückgeben, Duplikate sind nicht erlaubt
            return false;
        }

        return $new_url;
    }

    return $url;
}
```

### EP (Extension Point) URL_PROFILE_RESTRICTION

Mit diesem Extension Point kann man die Einschränkungen von außen beeinflussen.

Im nachfolgenden Beispiel werden Urls für News erzeugt, die erst 3 Tage später online gehen und damit bereits auch in der sitemap.xml erscheinen.

```php
rex_extension::register('URL_PROFILE_RESTRICTION', function (\rex_extension_point $ep) {
    $restrictions = $ep->getSubject();
    $profile = $ep->getParam('profile');

    if ($profile->getTableName() === 'rex_ao_news') {
        $profile->addColumnName('online_from');
        $restrictions[] = [
            'column'              => 'online_from',
            'comparison_operator' => '<=',
            'value'               => date('Y-m-d', strtotime('+3 days')),
        ];
        $ep->setSubject($restrictions);
    }
});
```

### EP (Extension Point) URL_SEO_TAGS

Hiermit können die verschiedenen HTML-Tags nachträglich beeinflusst werden.

```php
\rex_extension::register('URL_SEO_TAGS', function(\rex_extension_point $ep) {
    $tags = $ep->getSubject();
    dump($tags);
    $ep->setSubject($tags);
});
```

### EP (Extension Point) URL_TABLE_UPDATED

Dieser Extension Point wird getriggert, sobald die Tabelle der Urls sich ändert.

```php
\rex_extension::register('URL_PROFILE_RESTRICTION', function () {
});
```

## SEO-Methoden und EP (Extension Point) URL_SEO_TAGS

URL verwendet die Standard-Methode des YRewrite-SEO-Objekts.

```php
$seo = new rex_yrewrite_seo();
echo $seo->getTags();
```

Eine Anpassung der einzelnen Tags kann über den Extension Point `URL_SEO_TAGS` erreicht werden.

```php
use Url\Seo;
use Url\Url;

$seo = new Seo();
$manager = Url::resolveCurrent();
if ($manager) {
    \rex_extension::register('URL_SEO_TAGS', function(\rex_extension_point $ep) use ($manager) {
        $tags = $ep->getSubject();

        $titleValues = [];
        $article = rex_article::get($manager->getArticleId());
        $title = strip_tags($tags['title']);

        if ($manager->getSeoTitle()) {
            $titleValues[] = $manager->getSeoTitle();
        }
        if ($article) {
            $domain = rex_yrewrite::getDomainByArticleId($article->getId());
            $title = $domain->getTitle();
            $titleValues[] = $article->getName();
        }
        if (count($titleValues)) {
            $title = rex_escape(str_replace('%T', implode(' / ', $titleValues), $title));
        }
        if ('' !== rex::getServerName()) {
            $title = rex_escape(str_replace('%SN', rex::getServerName(), $title));
        }

        $tags['title'] = sprintf('<title>%s</title>', $title);
        $ep->setSubject($tags);
    });
}
$tags = $seo->getTags();
```

## Weitere Tipps

### Leere Einträge vermeiden

Werden URLs selbst erzeugt, z.B. über eine YForm-Tabelle, dann sollte das Feld oder die Feldkombination, aus der die URL generiert wird, nur einmal vorkommen (prüfen auf `unique`) und außerdem niemals leer sein (prüfen auf `empty`). Kommt das Feld oder die Feldkombination mehrfach vor, so wird ab der zweiten URL zusätzlich automatisch die ID des Datensatzes angehangen.

### YForm-Formular auf einer von Url-Add-on erzeugten Url

Befindet sich ein Formular auf einer Seite, die über eine URL des URL-Addon aufgerufen wurde, so muss die Ziel-URL des Formulars angepasst werden.

```php
$yform->setObjectparams('form_action', rex_getUrl('', '', [$manager->getProfile()->getNamespace() => $manager->getDatasetId()]));
```

oder

```php
$yform->setObjectparams('form_action', Url::getCurrent());
```

Weiere Infos zu den Objekt-Parametern von YForm befinden sich in der YForm-Doku.

### Einzelne Datensätze nicht in der sitemap.xml aufnehmen

Dazu kann man ein zusätzliches YForm-Feld anlegen der das Indexieren speichert. Im Url-Add-on werden dann zwei Profile angelegt und auf das Index-Feld gefiltert. Das eine Profil erhält zusätzlich die Info "In Sitemap aufnehmen".

## Debugging

* Sind alle gewünschten Domains in YRewrite vollständig und korrekt angegeben, einschließlich separater 404-Fehlerseite?
* Wurden Änderungen an der Datenbank vorgenommen, die sich mit dem Löschen des REDAXO-Caches. Datensätze, die außerhalb von REDAXO erstellt, verändert oder gelöscht werden, benötigen ein Auffrischen des Caches.
* Sind die URL-Profile korrekt oder haben sich Änderungen am verknüpften REDAXO-Artikel oder der Struktur der Datenbankfelder ergeben?
* Sind innerhalb der URL-Profile Einschränkungen vorgenommen worden? Bspw. können Datensätze gefiltert werden, die dann keine URL erzeugen. Die erzeugten URLs lassen sich im REDAXO-Backend überprüfen.

## Lizenz

MIT Lizenz, siehe [LICENSE.md](https://github.com/alexplusde/url/blob/main/LICENSE.md)  

## Autoren

[Alexander Walther](https://github.com/alxndr-w)

* <https://www.alexplus.de>  
* <https://github.com/alexplusde>

## Credits

Dieses Add-on ist ein Fork von `redaxo_url` von <https://github.com/tbaddade/redaxo_url>

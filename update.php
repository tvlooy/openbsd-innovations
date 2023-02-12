<?php

$dom = new DOMDocument();
$dom->loadHTML(file_get_contents('https://www.openbsd.org/innovations.html'));

$header = file_get_contents(__DIR__.'/header.html');
$footer = file_get_contents(__DIR__.'/footer.html');
$index = __DIR__.'/docs/index.html';

file_put_contents($index, $header);

$timeline = [];

$prevYear = 1996;
foreach ($dom->getElementsByTagName('ul') as $ul) {
    foreach ($ul->getElementsByTagName('li') as $li) {
        $element = strip_tags($li->ownerDocument->saveHTML($li), 'li');
        [$title, $body] = explode(':', $element.':', 2);

        if (strlen($title) > 100) {
            $body = $title.':'.$body;
            $title = '';
        }

        $body = trim($body, ':');
        if (empty($body)) {
            $body = $title;
            $title = '';
        }

        $year = '';
        if (preg_match_all('/\b\d{4}\b/', $element, $matches)) {
            $year = end($matches[0]);
        }
        $insertYear = $year;
        if (empty($insertYear)) {
            $insertYear = $prevYear;
        } else {
            $prevYear = $year;
        }

        $timeline[$insertYear][] = [
            'year' => $year,
            'title' => $title,
            'body' => $body,
        ];
    }
}

ksort($timeline, SORT_NUMERIC);

$releases = [];

foreach ($timeline as $year) {
    foreach ($year as $el) {
        $year = $el['year'];
        $title = $el['title'];
        $body = $el['body'];

        $version = '';
        if (preg_match('/OpenBSD \d{1}\.\d{1}/', $body, $matches)) {
            $version = str_replace('OpenBSD ', '', $matches[0]);
        }

        $html=<<<EOD
        <div class="timeline-item animate">
          <div class="timeline-content">
            <h2>${title}</h2>
        EOD;
        if (!empty($year)) {
            $html.="<time class=\"date\" datetime=\"${year}-01-01T00:00:00Z\">${year}</time>";
        }
        $html.=<<<EOD
            <p>
              ${body}
            </p>
        EOD;
        if (!empty($version) && !in_array($version, $releases)) {
            $v = str_replace('.', '', $version);
            $html.=<<<EOD
            <div class="timeline-image">
              <img
                alt="OpenBSD ${version}"
                data-entity-type="file"
                data-entity-uuid="ec067012-1070-4e32-a49a-092cab7e8fcc"
                src="assets/img/obsd${v}.gif"
                class="align-center"
                width="300"
                height="240"
                loading="lazy"
              />
            </div>
            EOD;
            $releases[] = $version;
        }
        $html.=<<<EOD
            <p>&nbsp;</p>
          </div>
        </div>
        EOD;

        file_put_contents($index, $html, FILE_APPEND);
    }
}

file_put_contents($index, $footer, FILE_APPEND);


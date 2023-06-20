Humanities Commons Open Repository Exchange (HumCORE)
================

HumCORE is a library-quality repository for sharing, discovering, retrieving, and archiving digital work. HumCORE provides CBOX members with a permanent, Open Access storage facility for their scholarly output, facilitating maximum discoverability and encouraging peer feedback.

The underlying software is a WordPress / BuddyPress plugin that connects the [Commons-in-a-Box][1] (CBOX) collaboration platform to a [Fedora][2]-based institutional repository system. This combined system is in use on [_MLA Commons_][3] and is being made available for use by other scholarly societies.

## Features

Easy file upload and entry of metadata. 
Supports a defined subject taxonomy as well as user defined tags. 
Full-text search and faceted search results. 
Supports a large number of item types and file formats. 

Item types that can be deposited in HumCORE include:

- Peer-reviewed journal articles
- Dissertations and theses
- Works-in-progress
- Conference papers
- Syllabi, abstracts, data sets, presentations, translations, book reviews, maps, charts, and more

HumCORE accepts the following file types:

- Audio files: mp3, ogg, wav
- Data files: csv, ods, sxc, tsv, xls, xlsx
- Image files: gif, jpeg, jpg, png, psd, tiff
- Mixed material and software (archive files): gz, rar, tar, zip
- Text files: doc, docx, htm, html, odp, odt, pdf, ppt, pptx, pps, rdf, rtf, sxi, sxw, txt, wpd, xml
- Video files: f4v, flv, mov, mp4

HumCORE accepts file sizes up to 100MB.

## Requirements

This plugin requires a working WordPress install with Commons-in-a-Box. This plugin also requires access to a Fedora repository, a Solr instance, and an [EZID][4] API key to allow creation of DOIs. This plugin uses the `WP_HTTP` class but also requires cURL to upload files to the Fedora repository and Solr index. This plugin works with the cbox-mla theme and will require some integration to work with other themes. The theme files can be found in the templates/deposits subdirectory.

Additional sofware used by this plugin:

- Solarium: a PHP Solr client library handling all the complex Solr query parameters using a well-documented API.
- Select2: jQuery-based replacement for select boxes.

## Installation

### Install source from GitHub

Install the source code in the Wordpress plugins directory:

```sh
cd /path/to/wp-content/plugins
git clone https://github.com/mlaa/humcore.git
```

Use [Composer][5] to install the additional dependencies:

```sh
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

## Configuration

Steps to configure the plugin can be found [here](docs/configure.md).

## Changelog

### 1.0.0
* Initial public release.


[1]: http://commonsinabox.org
[2]: http://www.fedora-commons.org
[3]: https://commons.mla.org
[4]: http://ezid.cdlib.org
[5]: https://getcomposer.org


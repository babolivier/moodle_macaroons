# Macaroons authentication plugin for Moodle

This plugin is part of a proof of concept on Macaroons I made [here](https://github.com/babolivier/das). It should be, however, totally usable.

The PHP implementation of Macaroons used in this plugin (which includes everything in `Macaroons`) has been developped by [immense](https://github.com/immense/php-macaroons).

## Install

Drop all the files in this repository to the `auth` directory (at the root of your Moodle setup), in a directory named `macaroons`.

In short, you can `cd` to Moodle's `auth` directory then run

```bash
git clone https://github.com/babolivier/moodle_macaroons macaroons
```

You may then need to rebuild Moodle's cache. To do so, run

```bash
php [your Moodle setup]/admin/cli/purge_caches.php
```

## Configuration

You'll then see a "Macaroons" line popping in the authentication management page in Moodle's admin panel. If you're not much familiar with this page, please head over [here](https://docs.moodle.org/32/en/Managing_authentication#Setting_the_authentication_method.28s.29).

Please give a look at the plugin's settings before using it. Mandatory settings are:

* Cookie name
* Secret
* Identifier format
* E-mail template

All are described on the plugin's settings page.

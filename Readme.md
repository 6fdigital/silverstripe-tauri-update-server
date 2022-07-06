# Silverstripe Tauri Update Server

A module turning silverstripe into a update server for tauri apps

# Requirements

SilverStripe 4+

## Installation Instructions

### Composer

1. ```composer require 6fdigital/silverstripe-tauri-update-server```
2. Visit http://yoursite.com/dev/build?flush=1 to rebuild the database.

### Manual

1. Place this directory in the root of your SilverStripe installation, rename
   the folder to `update-server`.
2. Visit http://yoursite.com/dev/build?flush=1 to rebuild the database.

## Concepts

Tauri apps coming shipped with a updater included. This requires a server responding
to the request of a tauri updater. The general documentation could be
found [here](https://tauri.app/v1/guides/distribution/updater).

This module will handle requests from your tauri-app(s) by serving some json, which
allows the tauri updater to determine whether an update are available or not. Just
add the url of your silverstripe installation as the endpoint within the tauri config.

The module allows to manage multiple applications with different releases and artifacts.
Simply go to the `Update Server` section within the CMS and add your information.

### Checking for updates

Add the url of you're silverstripe-installation to the endpoint section within the
tauri config:

```json
{
  "updater": {
    "active": true,
    "endpoints": [
      "https://some.tld/update/<app-name>/{{target}}-{{arch}}/{{current_version}}"
    ]
  }
}
```
This will return with some json data and status code 200 if a new version are available 
or with status-code 204 if not.

#### Semver Version Checks

To check whether an app has updates, we'll use the `composer/semver` package.
For more information see [here](https://getcomposer.org/doc/articles/versions.md#versions-and-constraints).

### Creating a new release
The module also supports creating a new release via a HTTP POST request. To create a
new release you need the following information:
* **Token** - If enabled on the application, you must serve a token with your request
* **Release Manifest** - See below for more information

#### Release Manifest
To release a new version of an application, you MUST create a release manifest and send
it with your request to the endpoint. Also, you must specify at least one artifact you
want to publish with your release.
```json
{
   "version":"<release-version>",
   "notes":"<release-notes>",
   "signature":"<tauri-artifact-signature>",
   "application":"<application-name>",
   "artifacts":[
      {
         "os":"<os (linux | darwin | windows)>",
         "arch":"arch (x86_64 | aarch64 | i686 | armv7)",
         "field":"<field-name>"
      }
   ]
}
```
For the request to function, you must create a `form-data` request and add your files under
the field names for each artifact (field), a `MANIFEST` field serving the above json as well as
a `TOKEN` field. The endpoint for adding new releases are available under `https://your.tld/release/add`.
More information about signing your builds could be found below under **Code Signing**.


## Code Signing

Each artifact must contain a valid signature defined by tauri during the app
builds.
Therefore, you MUST [create a pub/private-key](https://tauri.app/v1/guides/distribution/updater#signing-updates)
to sign your artifacts.

The signature for each release could be found in the appropriate `*.tar.gz.sig` file within each
single build target in the `target` folder your tauri-app. See Code Signing for more information. 


# Silverstripe Tauri Update Server
A module turning silverstripe in a update server for tauri apps

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
The module allows to provide a tauri-update-server capable of managing whether
new versions for a tauri-app are available or not. You simply create an 
application within the SilverStripe CMS to provide further details about
releases and artifacts related to your app(s).

When a tauri-app requests a check about whether an update is available or not,
it'll pass the `{{target}}` and `{{current_version}}` via the called url. 

### Application
A application DataObject are the starting point for providing an update-server
here you can manage your different versions of your application.

### Release
Via a release you're able to manage the new version's (major, minor, patch) of
your app. ALso, you're able to provide some notes for all of the releases.

### Artifact
With the Artifact you're able to provide os-related files for the Releases of 
your app.

## Code Signing
Each artifact must contain a valid signature defined by tauri during the app
builds.
Therefore, you MUST [create a pub/private-key](https://tauri.app/v1/guides/distribution/updater#signing-updates)
to sign your artifacts.

The signature for the new build artifacts are then outputted to the file 
mentioned on the last line of a `npm run tauri build`. See the 
`<app-name>.app.tar.gz.sig` path.

```bash
Password: 
<empty>
Deriving a key from the password and decrypting the secret key... done
        Info 1 updater archive at:
        Info         <absolute-project-path>/src-tauri/target/release/bundle/macos/tauri-app.app.tar.gz.sig
```


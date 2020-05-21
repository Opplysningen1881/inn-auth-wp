# inn-auth-wp
Wordpress plugin for Opplysningen INN

# Installation

## Prerequisites
1. A valid INN application account. Create yours on the [INN Self Service portal](https://inn-prod-ss.opplysningen.no/innss/).

## Manual installation
1. Compress all files in the src-directory to a zip file inn-auth.zip.
1. Upload the plugin to wordpress and activate it. `/wp-admin/plugin-install.php`
1. Fill in your application details in the settings `/wp-admin/options-general.php?page=inn-setting-admin`




# Usage

## Options


## Shortcodes

`[inn-login]`
Creates a login button. The user will be redirected to INN for SSO, and redirected back to the URL of the page

`[inn-checkout]`
Creates a checkout button. The user will be redirected to INN for address selection, and redirected back to the URL of the page.

`[inn-printmytoken]`
Prints the UserToken in a human friendly format.

# Flagged Revisions Access Control

Flagged Revisions Access Control (FRAC) is an extension for the [Flagged Revisions](https://www.mediawiki.org/wiki/Extension:FlaggedRevs) extension for [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki).

This extension is meant to simplify configuring Flagged Revisions to restrict access to unapproved page revisions. It grew out of the instructions on [this page](https://www.mediawiki.org/wiki/Extension:FlaggedRevs/Restricting_unapproved_revisions), using which non-users on a wiki can be prevented from seeing unreviewed content. Such configuration can be elaborated to fine-tune the access restrictions, which is what FRAC is meant to do.

## Features

Compared to the [example configuration for access restriction](https://www.mediawiki.org/wiki/Extension:FlaggedRevs/Restricting_unapproved_revisions) for Flagged Revisions:
- Non-reviewed namespaces (and pages marked exempt in `$wgFlaggedRevsWhitelist`) have no access restrictions. They can be read whether or not a viewer has the 'read' right.
- In the absence of the 'read' right, for pages with access restrictions, the right to read stable versions is given by the new 'readstable' right. By default, it is given to: '*', 'editor', 'reviewer', 'autoreview', 'sysop'.
- In the absence of full access to a page, those with the 'readstable' right can:
  - View a page with no additional URL parameters to see the stable version. (Preferences and `$wgFlaggedRevsExceptions` are overridden for those without 'read', to ensure they see the stable version whenever it exists instead of a permission error.)
  - View specific stable revisions of pages using the "oldid" and "direction" URL parameters. Diffs are also allowed when both versions are stable.
  - "View source" for stable revisions of pages. With no additional URL parameters, the source of the latest stable version is seen.
  - View the history of each page. (Enabled by default, but can be disabled. One might wish to disable it if concerned about it revealing some information about unstable revisions. But then, unrestricted special pages like "Recent changes" also reveal such information.)

## Installation

This extension has been tested with MediaWiki 1.25, but is likely to work with versions down to and including 1.19.

To download the extension using git, do the following from the main directory of your MediaWiki installation:
```
cd extensions/
git clone https://github.com/joelkp/FlaggedRevsAccessControl.git
```

Then include the following in the [LocalSettings.php](https://www.mediawiki.org/wiki/Manual:LocalSettings.php) file for your wiki. It must be included at some point after the corresponding lines for FlaggedRevs.
```php
## Flagged Revisions Access Control
require_once "$IP/extensions/FlaggedRevsAccessControl/FlaggedRevsAccessControl.php";
```

## Configuration

### Basic setup

To make this extension functional, read access must also be restricted. The example below disables it for anonymous users, while still giving it to all registered user. This can be modified according to whichever groups you want to have and not have access. (For general information on configuring user rights, see the [User rights](https://www.mediawiki.org/wiki/Manual:User_rights) MediaWiki manual page.)
```php
# Restrict all read access to logged-in users
$wgGroupPermissions['*']['read'] = false;
$wgGroupPermissions['user']['read'] = true;
```
The above can be included either above or below the FRAC installation in your LocalSettings.php file.

### Additional settings

Below is a list of additional settings. Changes to those with variable names beginning with "$fracg" must be set at some point after the installation lines for this extension in LocalSettings.php.
- `$wgWhitelistRead` and `$wgWhitelistReadRegexp`: [Two](https://www.mediawiki.org/wiki/Manual:$wgWhitelistRead) [standard](https://www.mediawiki.org/wiki/Manual:$wgWhitelistReadRegexp) MediaWiki settings which can be used to give full read access to specific pages. With FRAC, this only affects pages not otherwise exempt from access control.
- `$fracgAllowViewHistory`: For non-whitelisted pages in namespaces with access control, whether or not history can be viewed. By default, it is set to `true`.

## Use with other extensions

When further access restrictions are desired for a semi-public wiki, the [Lockdown](https://www.mediawiki.org/wiki/Extension:Lockdown) extension is probably the best option.

## License

[GNU General Public License 2.0 or later](https://www.gnu.org/copyleft/gpl.html)

## Contributing

Feel free to send any questions, ideas, suggestions, bug reports, patches, etc.

If you wish to email me, I'm "joelkpettersson" and I'm using Gmail.

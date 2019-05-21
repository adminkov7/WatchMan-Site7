=== WatchMan-Site7 ===

Contributors: adminkov
Plugin Name: WatchMan-Site7
Plugin URI: https://wordpress.org/plugins/watchman-site7/
Tags: login, blacklist, cron, statistic, security
Author: Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
Author URI: https://adminkov.bcr.by/
Donate link: https://adminkov.bcr.by/contact/
Requires at least: 4.5.1
Tested up to: 5.2
Requires PHP: 5.2.4 or higher
Stable tag: 3.1.1
Version: 3.1.1
License: GPLv2 or later
Initiation:	is dedicated to Inna Voronich

== Description ==

The plugin keeps a log of site visits, monitors system files and cron events of site. The main functions of the plugin are: 
1. Record the date and time of visit to the site by people, robots
2. The entry registration site visit: successful, unsuccessful, no registration, robots, members of black list
3. Country, city  of visitors site
4. Record information about the browser (User Agent) of the visitor
5. Recording site visits for various roles of visitors 
6. The deletion of unnecessary records on the visit in automatic and manual modes
7. Export records of visits to the site in an external file for later analysis
8. Automatic screen refresh mode using SSE technology (Server-Sent Events)
9. SMA - Simple Mail Agent, managing the mailboxes of your site, as well as mail.ru, yandex.ru, yahoo.com, gmail.com
10. Analyzes attacks targeting a website. Brute-force attack protection
11. Compatible with MULTISITE mode

<a href="https://adminkov.bcr.by/" target="_blank">Plugin home page</a>

<a href="https://www.youtube.com/watch?v=iB-7anPcUxU&list=PLe_4Q0gv64g3WgA1Mo_S3arSrK3htZ1Nt" target="_blank">Demo video - [RU]</a>

<a href="https://adminkov.bcr.by/doc/watchman-site7/api_doc/index.html" target="_blank">API Documentation</a>

<a href="https://adminkov.bcr.by/doc/watchman-site7/user_doc/index.htm" target="_blank">User Documentation</a>

==Features include:==

1. Filters I level: by date, by country, by visitor's roles
2. Filters II level: by logged, by unlogged, by login errors, by visits of robots, by visitors from the black list
3. Export into custom CSV file
4. Log auto-truncation
5. File editor: index.php
6. File editor: robots.txt
7. File editor: .htaccess
8. File editor: wp-config.php
9. Manage cron - events of site
10. Statistics of visits to the site
11. Built-in console for managing WordPress environment.
12. Widget: site visits count with automatic update of visits data
13. Geolocation of visitors to the site (only by the HTTPS protocol)
14. Information about the IP of the visitor
15. Black list of visitors and blocking the IP, or user name, or user agent for the selected period of time
16. Automatic updating of the list of site visits using SSE technology
17. SMA - Simple Mail Agent, managing the mailboxes of your site, as well as mail.ru, yandex.ru, yahoo.com, gmail.com
18. Google reCAPTCHA.

==Translations:==

- English [en_EN]
- Russian [ru_RU]

== Installation ==

In the mode of one site:
1. Install and activate like any other basic plugin
2. Define basic plugin settings menu: Visitors/settings
3. Click on the Screen Options tab to expand the options section. You'll be able to change the number of results per page as well as hide/display table columns

In the mode of multisite:
1. Go to the admin panel:
My Sites/ Network Admin/ Plugins
2. Choose the command:
Plugins / Add New (WatchMan-Site7)
3. Not use Network Activate
4. We go to the administrative panel of the main site:
Network Admin/ MainSite/ Dashboard/ Plugins
5. Activate the plugin WatchMan-Site7
6. Make the settings of the plugin:
WatchMan-Site7/ Settings
WatchMan-Site7/ Screen Options
7. We go to the administrative panel of the subordinate site (sub domain):
My Sub Domain/ Dashboard/ Plugins
8. Activate the plugin WatchMan-Site7
9. Make the settings of the plugin:
WatchMan-Site7/ Settings
WatchMan-Site7/ Screen Options

== Screenshots ==

1. Screen basic settings of the plugin
2. Screen basic settings of the plugin (continue). Insert only your key for the Google Maps API KEY field, which you will receive by clicking on the link: https://console.developers.google.com/apis/credentials
3. Setting up IMAP and SMTP access to mailboxes
4. Example of registering and receiving a Google Maps API key
5. Screen Options are available at the top of the this plugin page
6. Compliance of the information panel with filters II level
7. An example of working with a black list. The visitor's IP is automatically entered in .htaccess
8. Example of filling in the fields Black list for selected visitor's IP
9. File editor for: index.php, robots.txt, .htaccess, wp-config.php
10. Viewer wp-cron tasks
11. Statistic of visits to site
12. Geolocation of visitor to site
13. Managing the mailbox from this plugin

== Changelog ==

= 3.1.1 =
* Added to profile: country, city of registered user.

= 3.1 =
* Stable version of the plugin. Tested with WordPress 5.1

= 3.0.4 =
* Improved plugin control interface. Added sound notification.

= 3.0.3 =
* Eliminated plugin vulnerability discovered by WordPress developers. Previous versions of the plugin have been removed from the repository by the plugin developer

== Frequently Asked Questions ==

= Question: When you change the WHO-is service provider, the info panel shows not all the statistics of visits to your website. Why? =
The answer: Replace WHO-is provider need to spend as little as possible. First, you loop through each to determine the correctness of the information provided by the provider. The accuracy of the information about visitors, unfortunately, is different and depends on the region of residence of visitors. Therefore, select the best provider from four submitted for your area and use it. In the event of an accidental denial of service provider WHO-is service, proceed to another provider.

= Question: After the visitor's IP is on the Black List, what happens next? =
The answer: The plugin remembers your blocking decision and automatically enters a blocking entry in htaccess when it's time to start blocking. Then, when it's time to end the lock, the plug-in removes the blocking entry from the htaccess. However, a record of this IP is stored in the database. And when, after some time, this visitor will go to your site, his IP will be marked in red. Although the visitor will walk quietly through your pages. the expiration date has expired. By the way, you can not delete this entry until you clear the Black list field. Then you can safely delete this entry or it will be automatically deleted when the visitor log is truncated automatically.
= Question: After clicking the "wp-cron" button (at the bottom of the plug-in table), a modal window appears. What's next? =
The answer: In the modal window, a list of cron-events appears, which work on your site. You can click the - refresh button. After 20 seconds the list will be updated. If you bring the cursor to the cell - Source task, a context line will appear with the full path to the file - the source of this event. There are bad plugins, when they are deleted, there are stray events. You can see and delete them.
= Question: Why, sometimes when working on the main page of the plugin displays a message: an Error in establishing a connection to the database? =
The answer: This situation may occur when SSE mode is enabled. Disable this mode, do the rest of your work on the plugin page and re-enable SSE mode if you need it. This message, although unpleasant, will not happen in a bad situation. When SSE mode is enabled, the plugin makes one query per 10 seconds to the database. And in that moment, when you give a team - WordPress can't execute two commands at the same time. But I repeat-a bad situation will not happen.
= Question: How to use the SSE button correctly? =
The answer: The SSE function in the plugin should be used in cases when you want to WATCH for the arrival of new visitors to the site or for the receipt of new emails in the mailbox. In another case, when you want to perform some actions on the plugin page (for example: delete records using bulk actions), it is recommended before starting these steps to disable SSE. And then, when you've done your job, you can re-enable SSE to dynamically retrieve data.
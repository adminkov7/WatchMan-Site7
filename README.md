**WatchMan-Site7**

Contributors: adminkov
Plugin Name: WatchMan-Site7
Plugin URI: http://www.adminkov.bcr.by/watchman-site7/
Tags: login, black list, cron, IP, security
Author: Oleg Klenitskiy
Author URI: 	https://www.adminkov.bcr.by/category/wordpress/
Donate link: https://www.adminkov.bcr.by/category/wordpress/
Requires at least: WP 4.5
Tested up to: WP 4.9
Stable tag: 2.0
Version: 2.2.1
License: GPL3

**Description:**

The plugin keeps a log of site visits, monitors system files and site events. The main functions of the plugin are: 
1. Record the date and time of visit to the site by people, robots. 
2. The entry registration site visit: successful, unsuccessful, no registration. 
3. Country, city  of visitor site. 
4. Record information about the browser, OS of the visitor. 
5. Recording site visits for various categories of visitors 
6. The deletion of unnecessary records on the visit in automatic and manual modes. 
7. Export records of visits to the site in an external file for later analysis.
8. Automatic screen refresh mode using SSE technology (Server-Sent Events).

**Features include:**

1. filters I level: by date, by country, by visitor's roles
2. filters II level: by logged, by unlogged, by login errors, by visits of robots, by visitors from the black list
3. export into CSV file
4. log auto-truncation
5. manage cron tasks
6. file editor: index.php
7. file editor: robots.txt
8. file editor: .htaccess
9. file editor: wp-config.php
10. manage cron - events
11. statistics of visits to the site
12. widget: Counter of visits to the site
13. geolocation of visitors to the site (only by the HTTPS protocol)
14. information about the IP of the visitor
15. black list of visitors and blocking the IP for the selected period of time
16. blocking intrusive robots
17. Automatic updating of the list of site visits using SSE technology

**Translations:**

- English [en_EN]
- French  [fr_FR]
- German  [de_DE]
- Italian [it_IT]
- Russian [ru_RU]

**Installation:**

1. Install and activate like any other basic plugin.
2. Define basic plugin settings menu: Visitors/settings. 
3. Click on the Screen Options tab to expand the options section. You'll be able to change the number of results per page as well as hide/display table columns.

**Upgrade Notice:**

Missing.

**Screenshots:**

1. Screen basic settings of the plugin
2. Screen basic settings of the plugin (continue). Insert only your key for the Google Maps API KEY field, which you will receive by clicking on the link: https://console.developers.google.com/apis/credentials
3. Example of registering and receiving a Google Maps API key
4. Screen Options are available at the top of the this plugin page
5. Compliance of the information panel with filters II level
6. An example of working with a black list. The visitor's IP is automatically entered in .htaccess
7. Example of filling in the fields Black list for selected visitor's IP

**Changelog:**
**2.1.1**
Expanded the functionality of the widget.

**2.2.1**
Automatic screen refresh mode using SSE technology (Server-Sent Events).

**Frequently Asked Questions:**

= Question: When you change the WHO-is service provider, the info panel shows not all the statistics of visits to your website. Why? =
The answer: Replace WHO-is provider need to spend as little as possible. First, you loop through each to determine the correctness of the information provided by the provider. The accuracy of the information about visitors, unfortunately, is different and depends on the region of residence of visitors. Therefore, select the best provider from four submitted for your area and use it. In the event of an accidental denial of service provider WHO-is service, proceed to another provider.

= Question: After the visitor's IP is on the Black List, what happens next? =
The answer: The plugin remembers your blocking decision and automatically enters a blocking entry in htaccess when it's time to start blocking. Then, when it's time to end the lock, the plug-in removes the blocking entry from the htaccess. However, a record of this IP is stored in the database. And when, after some time, this visitor will go to your site, his IP will be marked in red. Although the visitor will walk quietly through your pages. the expiration date has expired. By the way, you can not delete this entry until you clear the Black list field. Then you can safely delete this entry or it will be automatically deleted when the visitor log is truncated automatically.
= Question: After clicking the "wp-cron" button (at the bottom of the plug-in table), a modal window appears. What's next? =
The answer: In the modal window, a list of cron-events appears, which work on your site. You can click the - refresh button. After 20 seconds the list will be updated. If you bring the cursor to the cell - Source task, a context line will appear with the full path to the file - the source of this event. There are bad plugins, when they are deleted, there are stray events. You can see and delete them.

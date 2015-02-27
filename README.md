# yfp-ganalylitcs-basic

YFP Google Analytics Settings is a WordPress plugin that adds the Google Analytics JavaScript (Universal) code at the beginning or end of your page by using either the `wp_head` or `wp_footer` hook. 
The code is for Google's "Universal Analytics", which is their currunt standard.

This is very basic and does not support the many options that Analytics now offers. But if you just want to turn tracking on, this is the way to go.


## Installation
The same as every other plugin: put the `yfp-ganalylitcs-basic` directory in your plugins folder and then activate the plugin.

1. Add your Google Analytics Tracing ID. 

2. Select Yes for the _Enable tracking_ radio button. You cannot enable the plugin without a Tracking ID.


## Options
For the most part these are only of interest to developers.

- Add to head or tail - Google recommends putting their code in the Head section of the page. For page loading speed, script should go at the end. My experience is that it works both ways.

- Use console logging - The page that is being logged will be written to the console. This is helpful if you are using any Developer Tools.

- Servers to not track - You can add a semicolon (;) separated list of servers to not track. This follows the way Google says to do it, but they do it in a way that logging still occurs. Details below.

### Servers to ignore

Google indicates that Analytics can go into a test mode by include the option `'cookieDomain': 'none'`. That option is set whenever the server matches one of the items in the ignore list.

The comparison is done only to the end of the host's identification string, so the more information you put the less likely that you will ignore something you don't want to.

For example, I set up test servers using the suffix '.local' instead of '.com' or other common public domain endings. Adding the text '.local' to the `Servers to not track` field will keep any test servers from being tracked.

